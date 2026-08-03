<?php
// Usage: include '_layout.php' after setting $pageTitle and $pageSubtitle
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle ?? 'Contact') ?> — <?= SITE_NAME ?></title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: Arial, sans-serif; background: #f3f4f6; color: #111827; }
.header { background: #222b59; padding: 1.25rem 2rem; }
.header a { color: #a5b4d4; text-decoration: none; font-size: .875rem; }
.header a:hover { color: #fff; }
.header h1 { color: #fff; font-size: 1.4rem; font-weight: 700; margin-top: .2rem; }
.container { max-width: 700px; margin: 2.5rem auto; padding: 0 1.25rem 3rem; }
.form-card { background: #fff; border: 1px solid #d1d5db; border-radius: 10px; padding: 2rem; box-shadow: 0 1px 4px rgba(0,0,0,.07); }
.form-title { font-size: 1.3rem; font-weight: 700; color: #222b59; margin-bottom: .35rem; }
.form-sub { font-size: .9rem; color: #6b7280; margin-bottom: 1.75rem; line-height: 1.5; }
.section-label { font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: #9ca3af; margin: 1.5rem 0 .9rem; border-bottom: 1px solid #e5e7eb; padding-bottom: .4rem; }
.section-label:first-of-type { margin-top: 0; }
label { display: block; font-size: .85rem; font-weight: 600; color: #374151; margin-bottom: .3rem; }
label .req { color: #dc2626; }
input[type=text], input[type=email], input[type=tel], input[type=number], select, textarea {
    width: 100%; padding: .55rem .75rem; border: 1px solid #d1d5db; border-radius: 6px;
    font-size: .925rem; font-family: Arial, sans-serif; color: #111827; background: #fff;
    transition: border-color .15s;
}
input:focus, select:focus, textarea:focus { outline: none; border-color: #222b59; box-shadow: 0 0 0 3px rgba(34,43,89,.1); }
textarea { resize: vertical; }
.field { margin-bottom: 1rem; }
.half-row { width: 100%; border-collapse: collapse; }
.half-row td { width: 50%; vertical-align: top; }
.half-row td:first-child { padding-right: .5rem; }
.half-row td:last-child { padding-left: .5rem; }
.checkbox-group label { font-weight: 400; display: flex; align-items: center; gap: .5rem; margin-bottom: .4rem; cursor: pointer; }
.checkbox-group input[type=checkbox] { width: auto; margin: 0; cursor: pointer; }
.radio-group label { font-weight: 400; display: flex; align-items: center; gap: .5rem; margin-bottom: .4rem; cursor: pointer; }
.radio-group input[type=radio] { width: auto; margin: 0; cursor: pointer; }
.hint { font-size: .8rem; color: #9ca3af; margin-top: .25rem; }
.conditional { display: none; margin-top: .75rem; }
.btn-submit { background: #222b59; color: #fff; border: none; padding: .75rem 2rem; font-size: 1rem; font-weight: 700; border-radius: 7px; cursor: pointer; width: 100%; margin-top: 1.5rem; transition: background .15s; }
.btn-submit:hover { background: #1a2147; }
.alert { padding: .9rem 1.1rem; border-radius: 7px; margin-bottom: 1.25rem; font-size: .95rem; }
.alert--success { background: #f0fdf4; border: 1px solid #86efac; color: #166534; }
.alert--error { background: #fef2f2; border: 1px solid #fca5a5; color: #b91c1c; }
</style>
</head>
<body>
<div class="header">
    <a href="index.php">&larr; Back to forms</a>
    <h1><?= SITE_NAME ?></h1>
</div>
<div class="container">
