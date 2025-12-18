<?php

function gen_id($prefix = "ID") {
    return $prefix . "_" . uniqid();
}
