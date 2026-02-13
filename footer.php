<?php
  $currentPage = basename($_SERVER['PHP_SELF']);
?>

</div>

<a class="scroll-to-top rounded" href="#page-top">
    <i class="fas fa-angle-up"></i>
</a>

<div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Ready to Leave?</h5>
                <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body">Select "Logout" below if you are ready to end your current session.</div>
            <div class="modal-footer">
                <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                <a class="btn btn-primary" href="./index.php">Logout</a>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap core JavaScript-->
<script src="vendor/jquery/jquery.min.js"></script>
<script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

<!-- Core plugin JavaScript-->
<script src="vendor/jquery-easing/jquery.easing.min.js"></script>

<!-- Page level plugin JavaScript-->
<script src="vendor/chart.js/Chart.min.js"></script>
<script src="vendor/datatables/jquery.dataTables.js"></script>
<script src="vendor/datatables/dataTables.bootstrap4.js"></script>

<!-- Custom scripts for all pages-->
<script src="js/sb-admin.min.js"></script>

<!-- Demo scripts for this page-->
<script src="js/demo/datatables-demo.js"></script>
<script src="js/demo/chart-area-demo.js"></script>
<script src="./js/main.js?v=<?php echo filemtime(__DIR__ . '/js/main.js'); ?>"></script>

<!-- global back button -->
<script>
  (function() {
    const sidebar = document.querySelector('.sidebar');
    const toggle = document.getElementById('sidebarToggle');
    const backdrop = document.getElementById('sidebarBackdrop');
    const closeBtn = document.querySelector('.sidebar-close');

    if (toggle && sidebar) {
      toggle.addEventListener('click', function(e) {
        e.preventDefault();
        // Let sb-admin.js handle the class toggle; only sync the backdrop state.
        setTimeout(() => {
          if (backdrop) backdrop.classList.toggle('is-open', sidebar.classList.contains('toggled'));
        }, 0);
      });
    }
    if (closeBtn && sidebar) {
      closeBtn.addEventListener('click', function() {
        sidebar.classList.remove('toggled');
        if (backdrop) backdrop.classList.remove('is-open');
      });
    }
    if (backdrop && sidebar) {
      backdrop.addEventListener('click', function() {
        sidebar.classList.remove('toggled');
        backdrop.classList.remove('is-open');
      });
    }

  })();
</script>

<script>
  (function(){
    const backBtn = document.getElementById('globalBackBtn');
    if (backBtn) {
      backBtn.remove();
    }
  })();
</script>

<script async type='module' src='https://interfaces.zapier.com/assets/web-components/zapier-interfaces/zapier-interfaces.esm.js'></script>
<style>
  zapier-interfaces-chatbot-embed { position: fixed; left: 16px; bottom: 16px; z-index: 9999; }
</style>
<zapier-interfaces-chatbot-embed is-popup='true' chatbot-id='cmli3f63z00anafk5l3zuaaqo'></zapier-interfaces-chatbot-embed>

</body>

</html>
