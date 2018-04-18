<?php
include_once 'auth_verify.php';
include_once 'header.php';
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <a class="navbar-brand" href="#">Yap</a>
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarText" aria-controls="navbarText" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarText">
        <ul class="navbar-nav mr-auto">
            <li class="nav-item active">
                <a class="nav-link" href="#">Home <span class="sr-only">(current)</span></a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#"></a>
            </li>
        </ul>
        <ul class="nav justify-content-end">
            <li class="nav-item">
                <button type="button"
                        class="btn btn-danger"
                        id="log-out-button"
                        onclick="location.href='logout.php';">Log Out</button>
            </li>
        </ul>
    </span>
    </div>
</nav>
