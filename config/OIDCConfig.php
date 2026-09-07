<?php

namespace IdPTestApp\Config;

class OIDCConfig {
  public static function all(): array {
    return EnvClass::get('OIDC', []);
  }
}