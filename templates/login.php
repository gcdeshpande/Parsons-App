<section class="panel login-panel">
    <h1>Log in</h1>
    <p>Pick your role and jump back into the puzzle arena.</p>
    <form method="post" class="form-grid">
        <label>
            <span>Display name</span>
            <input type="text" name="username" required placeholder="CodeHero42">
        </label>
        <label>
            <span>Role</span>
            <select name="role">
                <option value="player">Player</option>
                <option value="admin">Admin</option>
            </select>
        </label>
        <button class="btn primary" type="submit">Enter playground</button>
    </form>
</section>
