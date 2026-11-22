<#
.SYNOPSIS
  SDMS backend smoke tests — exercises auth, RBAC, upload, download gate,
  approval workflow, and audit logging over real HTTP against XAMPP.

.USAGE
  powershell -NoProfile -ExecutionPolicy Bypass -File tests\backend-smoke.ps1
  (XAMPP Apache + MySQL must be running; database must be imported.)

  IMPORTANT: test uploads/documents created by this script stay in the database
  (titles "Smoke ... <stamp>"). Admins can soft-delete them from the UI.
#>
param([string]$BaseUrl = 'http://localhost/SDMS/public')

$ErrorActionPreference = 'Stop'
$script:pass = 0
$script:fail = 0
$stamp   = [guid]::NewGuid().ToString('N').Substring(0, 8)
$fixture = Join-Path $PSScriptRoot 'fixtures\sample.txt'

function Pass([string]$name) { $script:pass++; Write-Host ("PASS  " + $name) -ForegroundColor Green }
function Fail([string]$name, [string]$detail) { $script:fail++; Write-Host ("FAIL  " + $name + "  -- " + $detail) -ForegroundColor Red }
function CodeOf($err) { if ($err.Exception.Response) { [int]$err.Exception.Response.StatusCode } else { $null } }
function Get-Csrf($response) {
    if ($response.Content -match 'name="csrf_token"[^>]*value="([0-9a-f]{64})"') { $Matches[1] } else { $null }
}
function Login([string]$user, [string]$password) {
    $sess = $null
    $r1 = Invoke-WebRequest -Uri "$BaseUrl/login.php" -SessionVariable sess -UseBasicParsing
    $tok = Get-Csrf $r1
    if (-not $tok) { throw "no CSRF token found on login page" }
    $r2 = Invoke-WebRequest -Uri "$BaseUrl/login.php" -WebSession $sess -Method Post `
        -Body @{ username = $user; password = $password; csrf_token = $tok } -UseBasicParsing
    return @{ Session = $sess; Response = $r2 }
}
function MultipartPost([string]$url, $sess, [hashtable]$fields, [string]$filePath) {
    $boundary = '----sdms' + [guid]::NewGuid().ToString('N')
    $enc = [System.Text.Encoding]::UTF8
    $ms  = New-Object System.IO.MemoryStream
    foreach ($k in $fields.Keys) {
        $s = "--$boundary`r`nContent-Disposition: form-data; name=`"$k`"`r`n`r`n" + $fields[$k] + "`r`n"
        $b = $enc.GetBytes($s); $ms.Write($b, 0, $b.Length)
    }
    $fileName = [System.IO.Path]::GetFileName($filePath)
    $s = "--$boundary`r`nContent-Disposition: form-data; name=`"document_file`"; filename=`"$fileName`"`r`nContent-Type: text/plain`r`n`r`n"
    $b = $enc.GetBytes($s); $ms.Write($b, 0, $b.Length)
    $b = [System.IO.File]::ReadAllBytes($filePath); $ms.Write($b, 0, $b.Length)
    $b = $enc.GetBytes("`r`n--$boundary--`r`n"); $ms.Write($b, 0, $b.Length)
    Invoke-WebRequest -Uri $url -WebSession $sess -Method Post -Body $ms.ToArray() `
        -ContentType ("multipart/form-data; boundary=" + $boundary) -UseBasicParsing
}
function Get-DocId($sess, [string]$title) {
    $r = Invoke-WebRequest -Uri "$BaseUrl/documents/index.php" -WebSession $sess -UseBasicParsing
    foreach ($line in ($r.Content -split "`n")) {
        if ($line -match [regex]::Escape($title) -and $line -match 'view\.php\?id=(\d+)') { return [int]$Matches[1] }
    }
    return $null
}

Write-Host "=== SDMS backend smoke tests vs $BaseUrl (stamp $stamp) ==="

# --- T1: guest is pushed to sign-in -----------------------------------------
try {
    $sess = $null
    $r = Invoke-WebRequest -Uri "$BaseUrl/" -SessionVariable sess -UseBasicParsing
    if ($r.Content -match 'Sign in') { Pass 'T1 guest redirected to sign-in' } else { Fail 'T1' 'did not land on the sign-in page' }
} catch { Fail 'T1' $_.Exception.Message }

# --- T2: POST without CSRF token is rejected ---------------------------------
try {
    Invoke-WebRequest -Uri "$BaseUrl/login.php" -Method Post -Body @{ username = 'admin'; password = 'Admin@123' } -UseBasicParsing | Out-Null
    Fail 'T2' 'POST without CSRF token unexpectedly succeeded'
} catch {
    if ((CodeOf $_) -eq 403) { Pass 'T2 CSRF-less POST rejected with 403' } else { Fail 'T2' ('unexpected status ' + (CodeOf $_)) }
}

# --- T3: wrong password → generic error --------------------------------------
try {
    $bad = Login 'admin' 'WrongPassword1'
    if ($bad.Response.Content -match 'Invalid username or password') { Pass 'T3 wrong password shows generic error' }
    else { Fail 'T3' 'generic error message not found' }
} catch { Fail 'T3' $_.Exception.Message }

# --- T4: admin login ----------------------------------------------------------
try {
    $admin = Login 'admin' 'Admin@123'
    $script:adminSess = $admin.Session
    if ($admin.Response.Content -match 'Admin dashboard') { Pass 'T4 admin login works, lands on dashboard' }
    else { Fail 'T4' 'admin dashboard not detected' }
} catch { Fail 'T4' $_.Exception.Message }

# --- T5: viewer blocked from admin area --------------------------------------
try {
    $viewer = Login 'viewer' 'Viewer@123'
    $script:viewerSess = $viewer.Session
    try {
        Invoke-WebRequest -Uri "$BaseUrl/admin/users.php" -WebSession $viewer.Session -UseBasicParsing | Out-Null
        Fail 'T5' 'viewer opened an admin page with HTTP 200'
    } catch {
        if ((CodeOf $_) -eq 403) { Pass 'T5 viewer blocked from admin area (403)' }
        else { Fail 'T5' ('unexpected status ' + (CodeOf $_)) }
    }
} catch { Fail 'T5 viewer login' $_.Exception.Message }

# --- T6: staff uploads two documents ------------------------------------------
$tApproved = "Smoke Approved $stamp"
$tPending  = "Smoke Pending $stamp"
try {
    $staff = Login 'staff' 'Staff@123'
    $script:staffSess = $staff.Session
    $tok = Get-Csrf (Invoke-WebRequest -Uri "$BaseUrl/documents/upload.php" -WebSession $staff.Session -UseBasicParsing)
    if (-not $tok) { throw 'no CSRF token on upload page' }
    MultipartPost "$BaseUrl/documents/upload.php" $staff.Session @{ csrf_token = $tok; title = $tApproved; category_id = '1'; description = 'smoke test, auto-approved category' } $fixture | Out-Null
    MultipartPost "$BaseUrl/documents/upload.php" $staff.Session @{ csrf_token = $tok; title = $tPending;  category_id = '2'; description = 'smoke test, approval required' } $fixture | Out-Null
    $pendingId  = Get-DocId $staff.Session $tPending
    $approvedId = Get-DocId $staff.Session $tApproved
    if ($pendingId -and $approvedId) {
        $script:pendingId = $pendingId
        Pass "T6 staff uploads accepted (approved #$approvedId, pending #$pendingId)"
    } else { Fail 'T6' ('uploaded documents not listed (pending=' + $pendingId + ', approved=' + $approvedId + ')') }
} catch { Fail 'T6' $_.Exception.Message }

# --- T7: viewer sees approved, cannot see pending -----------------------------
try {
    $vIdx = Invoke-WebRequest -Uri "$BaseUrl/documents/index.php" -WebSession $viewerSess -UseBasicParsing
    $seesApproved = $vIdx.Content -match [regex]::Escape($tApproved)
    $seesPending  = $vIdx.Content -match [regex]::Escape($tPending)
    if ($seesApproved -and -not $seesPending) { Pass 'T7 viewer sees approved document, pending hidden' }
    else { Fail 'T7' ('seesApproved=' + $seesApproved + ' seesPending=' + $seesPending) }
} catch { Fail 'T7' $_.Exception.Message }

# --- T8: viewer download of pending document is denied ------------------------
try {
    try {
        Invoke-WebRequest -Uri "$BaseUrl/documents/download.php?id=$script:pendingId" -WebSession $viewerSess -UseBasicParsing | Out-Null
        Fail 'T8' 'viewer downloaded a pending document (HTTP 200)'
    } catch {
        if ((CodeOf $_) -eq 403) { Pass 'T8 viewer download of pending document denied (403)' }
        else { Fail 'T8' ('unexpected status ' + (CodeOf $_)) }
    }
} catch { Fail 'T8' $_.Exception.Message }

# --- T9: admin approves, then viewer can download ------------------------------
try {
    $tok = Get-Csrf (Invoke-WebRequest -Uri "$BaseUrl/admin/approvals.php" -WebSession $adminSess -UseBasicParsing)
    if (-not $tok) { throw 'no CSRF token on approvals page' }
    Invoke-WebRequest -Uri "$BaseUrl/admin/approvals.php" -WebSession $adminSess -Method Post `
        -Body @{ csrf_token = $tok; action = 'approve'; document_id = $script:pendingId } -UseBasicParsing | Out-Null
    Pass 'T9 admin approved the pending document'
    $tmp = Join-Path $env:TEMP ('sdms-dl-' + $stamp + '.txt')
    Invoke-WebRequest -Uri "$BaseUrl/documents/download.php?id=$script:pendingId" -WebSession $viewerSess -OutFile $tmp -UseBasicParsing
    $content = Get-Content $tmp -Raw
    if ((Get-Item $tmp).Length -gt 0 -and $content -match 'integrity check payload') {
        Pass 'T10 viewer downloads the approved document, content verified'
    } else { Fail 'T10' 'downloaded file missing or content mismatch' }
    Remove-Item $tmp -ErrorAction SilentlyContinue
} catch { Fail 'T9/T10' $_.Exception.Message }

# --- T11: audit trail is visible and shows denials -----------------------------
try {
    $r = Invoke-WebRequest -Uri "$BaseUrl/admin/logs.php?tab=access" -WebSession $adminSess -UseBasicParsing
    if ($r.Content -match 'DENIED') { Pass 'T11 access log records the denied download attempt' }
    else { Fail 'T11' 'no DENIED entry found in access log' }
} catch { Fail 'T11' $_.Exception.Message }

# --- T12: staff cannot POST to an admin endpoint -------------------------------
try {
    $tok = Get-Csrf (Invoke-WebRequest -Uri "$BaseUrl/documents/index.php" -WebSession $staffSess -UseBasicParsing)
    try {
        Invoke-WebRequest -Uri "$BaseUrl/admin/approvals.php" -WebSession $staffSess -Method Post `
            -Body @{ csrf_token = $tok; action = 'approve'; document_id = 1 } -UseBasicParsing | Out-Null
        Fail 'T12' 'staff approvals POST unexpectedly succeeded'
    } catch {
        if ((CodeOf $_) -eq 403) { Pass 'T12 staff approvals POST blocked (403)' }
        else { Fail 'T12' ('unexpected status ' + (CodeOf $_)) }
    }
} catch { Fail 'T12' $_.Exception.Message }

# --- T13: logout ends the session ----------------------------------------------
try {
    $tok = Get-Csrf (Invoke-WebRequest -Uri "$BaseUrl/dashboard.php" -WebSession $staffSess -UseBasicParsing)
    $r = Invoke-WebRequest -Uri "$BaseUrl/logout.php" -WebSession $staffSess -Method Post -Body @{ csrf_token = $tok } -UseBasicParsing
    if ($r.Content -match 'Sign in') { Pass 'T13 logout returns to sign-in' }
    else { Fail 'T13' 'authenticated content still visible after logout' }
} catch { Fail 'T13' $_.Exception.Message }

Write-Host ""
Write-Host ("=== Results: " + $script:pass + " passed, " + $script:fail + " failed ===")
if ($script:fail -gt 0) { exit 1 }
