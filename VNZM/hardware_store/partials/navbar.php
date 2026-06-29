<nav class="navbar navbar-expand-lg">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center gap-2" href="index.php">
            <img src="logo/VNZM 2x4.png" alt="Logo" style="height:56px; width:auto; mask-image: radial-gradient(ellipse 70% 70% at 50% 50%, black 30%, transparent 100%); -webkit-mask-image: radial-gradient(ellipse 70% 70% at 50% 50%, black 30%, transparent 100%)">
        </a>
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
            <span style="color:#00c896; font-size:1.4rem;">☰</span>
        </button>
        <div class="collapse navbar-collapse" id="navMenu">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : '' ?>" href="dashboard.php">Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : '' ?>" href="index.php">Receipt</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'products.php' ? 'active' : '' ?>" href="products.php">Products</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'categories.php' ? 'active' : '' ?>" href="categories.php">Categories</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'transactions.php' ? 'active' : '' ?>" href="transactions.php">Transactions</a>
                </li>
            </ul>
            <span class="text-muted me-3" style="font-size:0.85rem; color:#4a7a5a !important;">
                <?= htmlspecialchars($_SESSION['username']) ?>
            </span>
            <a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a>
        </div>
    </div>
</nav>
