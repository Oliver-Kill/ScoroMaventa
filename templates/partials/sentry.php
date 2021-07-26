<?php if (!empty($_SERVER['HTTP_HOST']) && defined('SENTRY_DSN')): ?>

    <script src="https://js.sentry-cdn.com/156fcb150a77475494af80a26fa7bab2.min.js" crossorigin="anonymous"></script>

    <script>

        Sentry.init({
            dsn: '<?= SENTRY_DSN ?>',
            environment: "production",

            // Alternatively, use `process.env.npm_package_version` for a dynamic release version
            // if your build tool supports it.
            release: "ScoroMaventa",

            // Set tracesSampleRate to 1.0 to capture 100%
            // of transactions for performance monitoring.
            // We recommend adjusting this value in production
            tracesSampleRate: 1.0,

        });
    </script>

<?php endif; ?>