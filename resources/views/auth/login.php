<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card shadow-sm">
            <div class="card-header">
                <h4 class="mb-0">Login</h4>
            </div>

            <div class="card-body">

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">

                            <?php foreach ($errors as $error): ?>
                                <li>
                                    <?= htmlspecialchars(
                                        (string) $error,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </li>
                            <?php endforeach; ?>

                        </ul>
                    </div>
                <?php endif; ?>

                <form
                    action="/login"
                    method="POST"
                >
                    <?= \App\Core\Csrf::field() ?>

                    <div class="mb-3">
                        <label
                            for="email"
                            class="form-label"
                        >
                            Email
                        </label>

                        <input
                            type="email"
                            id="email"
                            name="email"
                            class="form-control"
                            autocomplete="username"
                            inputmode="email"
                            maxlength="255"
                            required
                            value="<?= htmlspecialchars(
                                (string) (
                                    $old['email'] ??
                                    ''
                                ),
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                        >
                    </div>

                    <div class="mb-3">
                        <label
                            for="password"
                            class="form-label"
                        >
                            Password
                        </label>

                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-control"
                            autocomplete="current-password"
                            maxlength="4096"
                            required
                        >
                    </div>

                    <button
                        type="submit"
                        class="btn btn-primary w-100"
                    >
                        Login
                    </button>

                </form>

            </div>
        </div>
    </div>
</div>