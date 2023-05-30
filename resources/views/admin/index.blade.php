@include('admin.partials.header')
<script type="text/javascript">
    if (!!window.MSInputMethodContext && !!document.documentMode) {
        document.write("<div style='text-align:center;'>Internet Explorer 11 is not supported.</div>");
    }

    function onAuthenticate() {
        $("#authenticateButton").attr("disabled", true);
        $("#auth").submit();
    }
</script>
<div id="signin" class="container">
    <div class="custom-control custom-switch" style="display: none;">
        <input type="checkbox" class="custom-control-input" id="darkSwitch" />
    </div>
    <form id="auth" class="form-signin" method="POST" action="admin/login">
        <div id="admin_title"><?php echo isset($GLOBALS['admin_title']) ? $GLOBALS['admin_title'] : ""; ?></div>
        <div id="yap-logo"></div>
        <div id="no-auth-message">
            <?php echo isset($_REQUEST['auth']) ? $GLOBALS['not_authorized'] : "" ?>
            <?php echo isset($_REQUEST['expired']) ? $GLOBALS['session_expired'] : "" ?>
        </div>
        <label for="inputEmail" class="sr-only">{{ $settings->word("username") }}</label>
        <input autocomplete="username" name="username" type="username" id="inputUsername" class="form-control" placeholder="{{ $settings->word("username") }}" required autofocus>
        <label for="inputPassword" class="sr-only">{{ $settings->word("password") }}></label>
        <input autocomplete="current-password" name="password" type="password" id="inputPassword" class="form-control" placeholder="{{ $settings->word("password") }}" required>
        <button id="authenticateButton" class="btn btn-lg btn-primary btn-block" onclick="onAuthenticate();">{{ $settings->word("authenticate") }}</button>
        <select class="form-control" name="override_word_language" id="admin_language">
            @foreach($settings->availableLanguages() as $key => $available_language)
                <option value="<?php echo $key; ?>"><?php echo $available_language; ?></option>
            @endforeach
        </select>
        <div id="version-info">
            <?php
            $_REQUEST['run_exclude_errors_check'] = "1";
            ?>
            @include('admin.partials.statusControl')
        </div>
    </form>
</div>
@include('admin.partials.footer')
