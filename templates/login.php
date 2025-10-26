<section class="panel login-panel">
    <h1>Log in</h1>
    <p>Enter your credentials to continue your streak.</p>
    <form method="post" action="index.php?page=login" class="form-grid">
        <label>
            <span>Username</span>
            <input type="text" name="username" required autocomplete="username" placeholder="CodeHero42">
        </label>
        <label>
            <span>Password</span>
            <input type="password" name="password" required autocomplete="current-password" placeholder="••••••••">
        </label>
        <button class="btn primary" type="submit">Enter playground</button>
    </form>
    <p class="auth-switch">Need an account? <a href="index.php?page=register">Register now</a>.</p>
</section>
