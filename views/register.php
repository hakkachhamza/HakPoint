<?php
$err='';
$success='';
$companySizes=[
    'small'=>'Small company',
    'medium'=>'Medium company',
    'big'=>'Big company',
];
if($_SERVER['REQUEST_METHOD']==='POST'){
    require_csrf();
    try{
        $res=ge_register_company_account($_POST);
        if(!empty($res['ok'])){
            redirect_to('index.php?page=dashboard&registered=1&onboarding=1');
        }
        $err=(string)($res['error'] ?? 'Registration failed. Please try again.');
    }catch(Throwable $e){
        $err=app_debug() ? $e->getMessage() : 'Registration failed. Please try again.';
    }
}
$old=fn($k,$default='') => e($_POST[$k] ?? $default);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Register company - Global Energie</title>
    <link rel="icon" href="assets/images/global-energie-icon.png">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-page register-page">
    <main class="auth-shell register-shell">
        <section class="auth-hero">
            <div class="auth-brand">
                <img class="auth-logo-img" src="assets/images/global-energie-logo.png" alt="Global Energie">
            </div>

            <h1>Create your company workspace.</h1>
            <p>Each company gets its own tenant, isolated data, and an administrator account secured by Row-Level Security.</p>
        </section>

        <section class="auth-card-wrap register-card-wrap">
            <form class="auth-card register-card" method="post" autocomplete="on">
                <?=csrf_field()?>
                <h2>Register</h2>
                <p class="auth-subtitle">Create a new company account.</p>

                <?php if($err): ?>
                    <div class="auth-alert"><?= e($err) ?></div>
                <?php endif; ?>

                <label for="company_name">Company name <span>*</span></label>
                <input id="company_name" name="company_name" type="text" required value="<?= $old('company_name') ?>" autocomplete="organization" placeholder="Example SARL">

                <label for="company_size">Company size <span>*</span></label>
                <select id="company_size" name="company_size" required>
                    <option value="">Choose size</option>
                    <?php foreach($companySizes as $value=>$label): ?>
                        <option value="<?= e($value) ?>" <?= (($_POST['company_size'] ?? '')===$value) ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>

                <label for="business_email">Business email <span>*</span></label>
                <input id="business_email" name="business_email" type="email" required value="<?= $old('business_email') ?>" autocomplete="email" placeholder="admin@company.com">

                <label for="password">Password <span>*</span></label>
                <input id="password" name="password" type="password" required minlength="8" autocomplete="new-password" placeholder="Minimum 8 characters">

                <label for="phone">Phone number <span>*</span></label>
                <input id="phone" name="phone" type="tel" required value="<?= $old('phone') ?>" autocomplete="tel" placeholder="+212 6 00 00 00 00">

                <div class="auth-grid-2">
                    <div>
                        <label for="country">Country <span>*</span></label>
                        <input id="country" name="country" type="text" required value="<?= $old('country','Morocco') ?>" autocomplete="country-name" placeholder="Morocco">
                    </div>
                    <div>
                        <label for="city">City <span>*</span></label>
                        <input id="city" name="city" type="text" required value="<?= $old('city') ?>" autocomplete="address-level2" placeholder="Casablanca">
                    </div>
                </div>

                <label for="zip">ZIP code <span>*</span></label>
                <input id="zip" name="zip" type="text" required value="<?= $old('zip') ?>" autocomplete="postal-code" placeholder="20000">

                <button type="submit" class="auth-submit">Create company account</button>

                <p class="auth-switch">Already have an account? <a href="index.php?page=login">Login</a></p>
            </form>
        </section>
    </main>
</body>
</html>
