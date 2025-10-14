<?php
require_once __DIR__ . '/../includes/auth.php';
?>
<aside id="sidebar" class="fixed inset-y-0 left-0 w-64 bg-slate-900 text-slate-100 transform -translate-x-full md:translate-x-0 transition-transform duration-200 z-20 shadow-xl overflow-y-auto">
  <div class="h-16 flex items-center px-4 border-b border-slate-800">
    <span class="font-semibold">Menu</span>
  </div>
  <nav class="p-2 space-y-1 text-sm">
    <a href="<?= base_url('index.php') ?>" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-slate-800">
      <span>Dashboard</span>
    </a>

    <?php if (is_admin()): ?>
      <div class="px-3 pt-3 text-xs uppercase tracking-wider text-slate-400">Administration</div>
      <a href="<?= base_url('admin/tests.php') ?>" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-slate-800">
        <span>Tests</span>
      </a>
      <a href="<?= base_url('admin/employees.php') ?>" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-slate-800">
        <span>Employees</span>
      </a>
      <a href="<?= base_url('admin/assignments.php') ?>" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-slate-800">
        <span>Assignments</span>
      </a>
      <a href="<?= base_url('admin/reports.php') ?>" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-slate-800">
        <span>Reports</span>
      </a>
    <?php endif; ?>

    <?php if (is_employee()): ?>
      <div class="px-3 pt-3 text-xs uppercase tracking-wider text-slate-400">Employee</div>
      <a href="<?= base_url('employee/assignments.php') ?>" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-slate-800">
        <span>My Tests</span>
      </a>
      <a href="<?= base_url('employee/history.php') ?>" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-slate-800">
        <span>My History</span>
      </a>
    <?php endif; ?>

    <?php if (!is_logged_in()): ?>
      <div class="px-3 pt-3 text-xs uppercase tracking-wider text-slate-400">Account</div>
      <a href="<?= base_url('login.php') ?>" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-slate-800">
        <span>Login</span>
      </a>
      <a href="<?= base_url('register.php') ?>" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-slate-800">
        <span>Register</span>
      </a>
    <?php endif; ?>
  </nav>
</aside>
