@include('admin.partials.header')
<script type="text/javascript">
    var sessionTimeoutMinutes = 20;
    setInterval(function() {
       location.href='<?php echo ("auth/timeout")?>';
    }, sessionTimeoutMinutes * 60000);
</script>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <a class="navbar-brand" href="#">Yap</a>
    <button class="navbar-toggler"
            type="button" data-toggle="collapse"
            data-target="#top-navbar"
            aria-controls="top-navbar"
            aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="top-navbar">
        <ul class="navbar-nav mr-auto">
            @foreach ($pages as $page)
            <li class="nav-item {{ basename($_SERVER['PHP_SELF']) == Str::of($page)->slug("-") ? "active" : "" }}">
                <a class="nav-link" href="{{ Str::of($page)->slug("-") }}">{{ $settings->word(Str::of($page)->slug("_")->toString()) }}</a>
            </li>
            @endforeach
        </ul>
        <ul class="nav justify-content-end">
            <li class="nav-item nav-item-right">
                <div class="custom-control custom-switch" style="height:50%;">
                    <input type="checkbox" class="custom-control-input" id="darkSwitch" />
                    <label class="custom-control-label" for="darkSwitch">🌙</label>
                </div>
            </li>
            <li class="nav-item">
                <?php if (isset($_SESSION['username'])) { ?>
                <button type="button"
                        class="btn btn-warning"
                        id="profile-button"
                        onclick="editUser('<?php echo $_SESSION['username']?>','<?php echo $_SESSION['auth_user_name_string']?>', '', '', 'profile') ">Profile</button>
                <?php } ?>
                <button type="button"
                        class="btn btn-danger"
                        id="log-out-button"
                        onclick="location.href='<?php echo ("auth/logout")?>';">{{ $settings->word("log_out") }} {{ $username }} </button>
            </li>
        </ul>
    </div>
</nav>
