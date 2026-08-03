<?php
ob_start();
$errors  = \App\Core\Session::getFlash('errors', []);
$success = \App\Core\Session::getFlash('success');
?>
<div class="auth-card">
    <div class="auth-card__header">
        <a href="/login" class="auth-card__back">← Back to sign in</a>
        <h1 class="auth-card__title">Reset your password</h1>
        <p class="auth-card__subtitle">Enter your email and we'll send you a reset link.</p>
    </div>

    <?php if ($success): ?>
        <div class="alert alert--success"><?= e($success) ?></div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert--error">
            <?php foreach ($errors as $field => $fieldErrors): ?>
                <?php foreach ((array) $fieldErrors as $error): ?>
                    <p><?= e($error) ?></p>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="/forgot-password" class="auth-form">
        <?= csrf_field() ?>
        <div class="form-group">
            <label for="email" class="form-label">Email address</label>
            <input type="email" id="email" name="email" class="form-input" value="<?= e(old('email')) ?>" autofocus required>
        </div>
        <button type="submit" class="btn btn--primary btn--full">Send reset link</button>
    </form>
</div>
<?php
$content = ob_get_clean();
include BASE_PATH . '/app/Views/layouts/auth.php';
