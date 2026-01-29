<?php require_once "../includes/auth_guard.php"; ?>
<!doctype html>
<html>
<head>
  <title>Crushify - Dashboard</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
  <?php include "../includes/navbar.php"; ?>

  <div class="container py-4">
    <div class="row g-3">
      <div class="col-md-12">
        <div class="p-4 bg-white rounded shadow-sm">
          <h2 class="mb-2">Find Your Perfect Match 💗</h2>
          <p class="text-muted mb-0">This is your dashboard home. We’ll place About Us, Our Services, and Contact Us sections here exactly like your Figma.</p>
        </div>
      </div>

      <div class="col-md-4">
        <div class="p-3 bg-white rounded shadow-sm h-100">
          <h5>About Us</h5>
          <p class="text-muted">Short description about Crushify...</p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="p-3 bg-white rounded shadow-sm h-100">
          <h5>Our Services</h5>
          <p class="text-muted">Match suggestions, messaging, notifications...</p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="p-3 bg-white rounded shadow-sm h-100">
          <h5>Contact Us</h5>
          <p class="text-muted">Email/phone/social links...</p>
        </div>
      </div>
    </div>
  </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
