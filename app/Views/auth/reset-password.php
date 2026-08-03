<?php
ob_start();
$errors = \App\Core\Session::getFlash('errors', []);
?>
<div class="auth-card">
    <div class="auth-card__header">
        <h1 class="auth-card__title">Set new password</h1>
        <p class="auth-card__subtitle">Choose a strong password for your account.</p>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert--error">
            <?php foreach ($errors as $field => $fieldErrors): ?>
                <?php foreach ((array) $fieldErrors as $error): ?>
                    <p><?= e($error) ?></p>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="/reset-password" class="auth-form">
        <?= csrf_field() ?>
        <input type="hidden" name="token" value="<?= e($token ?? '') ?>">

        <div class="form-group">
            <label for="email" class="form-label">Email address</label>
            <input type="email" id="email" name="email" class="form-input" value="<?= e(old('email')) ?>" required>
        </div>

        <div class="form-group">
            <label for="password" class="form-label">New password</label>
            <input type="password" id="password" name="password" class="form-input" required minlength="8">
        </div>

        <div class="form-group">
            <label for="password_confirmation" class="form-label">Confirm password</label>
            <input type="password" id="password_confirmation" name="password_confirmation" class="form-input" required>
        </div>

        <button type="submit" class="btn btn--primary btn--full">Reset password</button>
    </form>
</div>
<?php
$content = ob_get_clean();
include BASE_PATH . '/app/Views/layouts/auth.php';
