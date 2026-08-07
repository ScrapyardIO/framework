<?php

namespace Fabricate\JsonSchema;

enum DeserializerLimit: int
{
    case MAX_NODES = 20000;
}
