</main>
<footer class="container py-3 text-muted small d-print-none">
  Secure Document Management System · ITEC 85 · Information Security · CvSU-CEIT
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
<script src="<?= e(url('assets/js/app.js')) ?>"></script>
</body>
</html>
