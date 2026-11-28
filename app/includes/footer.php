</main>
<footer class="sdms-footer container-xl py-4 mt-5 text-muted small d-print-none border-top">
  <div class="d-flex flex-column flex-md-row align-items-center justify-content-between gap-3 text-center text-md-start">
    <div>
      <div class="fw-bold text-body">Cavite State University · Don Severino delas Alas Campus</div>
      <div class="text-muted small">College of Engineering and Information Technology · Department of Information Technology</div>
    </div>
    <div class="text-md-end text-muted small">
      <div>Secure Document Management System (SDMS) · ITEC 85 Information Security</div>
      <div>Official Academic Document Repository &copy; <?= date('Y') ?></div>
    </div>
  </div>
</footer>

<!-- Shared native confirmation dialog: opened by app.js whenever a form
     marked .js-confirm is submitted. The browser provides focus containment,
     Esc-to-close and focus return to the button that opened it. -->
<dialog id="confirm-dialog" class="sdms-dialog" aria-labelledby="confirm-dialog-title">
  <form method="dialog">
    <h2 id="confirm-dialog-title" class="h5 mb-3">Please confirm</h2>
    <p id="confirm-dialog-message" class="mb-4">Are you sure?</p>
    <div class="d-flex justify-content-end gap-2">
      <button value="cancel" class="btn btn-outline-secondary" autofocus>Cancel</button>
      <button value="confirm" class="btn btn-danger">Yes, continue</button>
    </div>
  </form>
</dialog>

<script src="<?= e(url('assets/js/bootstrap.bundle.min.js')) ?>"></script>
<script src="<?= e(url('assets/js/app.js?v=3.1-perf')) ?>"></script>
</body>
</html>
