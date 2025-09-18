<!-- Toast konténer jobb alsó sarokban -->
<div class="toast-container position-fixed bottom-0 end-0 p-3">
  <div id="liveToast" class="toast align-items-center border-0" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="d-flex">
      <div class="toast-body" id="toast-message"></div>
      <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Bezárás"></button>
    </div>
  </div>
</div>

<script>
  document.addEventListener("DOMContentLoaded", () => {
    const flash = document.getElementById("flash-data");
    if (flash) {
      const msg = flash.getAttribute("data-message");
      const type = flash.getAttribute("data-type") || "success";
      const toastEl = document.getElementById("liveToast");
      const toastBody = document.getElementById("toast-message");

      toastBody.textContent = msg;

      
      toastEl.classList.remove("text-bg-success","text-bg-danger","text-bg-warning","text-bg-info");
      toastEl.classList.add("text-bg-" + type);

      const toast = new bootstrap.Toast(toastEl, { delay: 4000 });
      toast.show();
    }
  });
</script>


<!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<script>
  (function(){
    const input = document.getElementById('vonalkodInput');
    const list  = document.getElementById('barcodeHints');
    if (!input || !list) return;

    let lastTerm = '';
    input.addEventListener('input', async () => {
      const term = input.value.trim();
      if (term.length < 2 || term === lastTerm) return;
      lastTerm = term;
      try {
        const res = await fetch('autocomplete.php?term=' + encodeURIComponent(term));
        if (!res.ok) return;
        const data = await res.json(); // pl.: ["LK235345","LK235346",...]
        list.innerHTML = '';
        data.forEach(v => {
          const opt = document.createElement('option');
          opt.value = v;
          list.appendChild(opt);
        });
      } catch(e) {  }
    });
  })();
</script>
