<section class="panel login-panel">
    <h1>Create your account</h1>
    <p>Claim a handle to start earning XP across the Parsons playground.</p>
    <form method="post" action="index.php?page=register" class="form-grid">
        <label>
            <span>Username</span>
            <input type="text" name="username" required autocomplete="username" placeholder="SyntaxSorcerer">
        </label>
        <label>
            <span>Password</span>
            <input type="password" name="password" required autocomplete="new-password" minlength="8" placeholder="At least 8 characters">
        </label>
        <label>
            <span>Confirm password</span>
            <input type="password" name="password_confirm" required autocomplete="new-password" minlength="8" placeholder="Repeat your password">
        </label>
        <button class="btn primary" type="submit">Create account</button>
    </form>
    <p class="auth-switch">Already registered? <a href="index.php?page=login">Log in</a>.</p>
</section>
