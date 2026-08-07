<?php

namespace Fabricate\Contracts\Routing;

interface UrlRoutable
{
    public function getRouteKey();
    public function getRouteKeyName();
    public function resolveRouteBinding($value, $field = null);
    public function resolveSoftDeletableRouteBinding($value, $field = null);
    public function resolveChildRouteBinding($childType, $value, $field);
    public function resolveSoftDeletableChildRouteBinding($childType, $value, $field);
}
