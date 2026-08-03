<?php
ob_start();
$errors  = \App\Core\Session::getFlash('errors', []);
$success = \App\Core\Session::getFlash('success');
?>
<div class="auth-card">
    <div class="auth-card__header">
        <div class="auth-logo">
            <span class="auth-logo__text">USSCOS</span>
        </div>
        <h1 class="auth-card__title">Welcome back</h1>
        <p class="auth-card__subtitle">Sign in to your account</p>
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

    <form method="POST" action="/login" class="auth-form" novalidate>
        <?= csrf_field() ?>

        <div class="form-group">
            <label for="email" class="form-label">Email address</label>
            <input
                type="email"
                id="email"
                name="email"
                class="form-input <?= isset($errors['email']) ? 'is-error' : '' ?>"
                value="<?= e(old('email')) ?>"
                autocomplete="email"
                autofocus
                required
            >
        </div>

        <div class="form-group">
            <label for="password" class="form-label">
                Password
                <a href="/forgot-password" class="form-label__link">Forgot password?</a>
            </label>
            <input
                type="password"
                id="password"
                name="password"
                class="form-input <?= isset($errors['password']) ? 'is-error' : '' ?>"
                autocomplete="current-password"
                required
            >
        </div>

        <div class="form-group form-group--inline">
            <label class="form-checkbox">
                <input type="checkbox" name="remember" value="1">
                <span>Remember me for 30 days</span>
            </label>
        </div>

        <button type="submit" class="btn btn--primary btn--full">Sign in</button>
    </form>
</div>
<?php
$content = ob_get_clean();
include BASE_PATH . '/app/Views/layouts/auth.php';
