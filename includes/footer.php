      </div>
      <footer class="mt-8 border-t border-slate-200 bg-white">
        <div class="max-w-7xl mx-auto px-4 py-6 text-sm text-slate-500 flex justify-between">
          <span>&copy; <?= date('Y') ?> Interview & Testing Portal</span>
          <span>Built with Tailwind, Alpine.js, PHP</span>
        </div>
      </footer>
    </main>
  </div>
  <script>
    const toggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    if (toggle && sidebar) {
      toggle.addEventListener('click', () => {
        sidebar.classList.toggle('-translate-x-full');
      });
    }
  </script>
</body>
</html>
