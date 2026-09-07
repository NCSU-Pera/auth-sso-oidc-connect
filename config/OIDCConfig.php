<?php

namespace SpTestApp\Config;

class OIDCConfig {
  public static function all(): array {
    return EnvClass::get('OIDC', []);
  }
}