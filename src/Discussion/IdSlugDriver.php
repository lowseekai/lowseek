<?php

namespace ISeekUp\Discussion;

use Flarum\Database\AbstractModel;
use Flarum\Discussion\IdWithTransliteratedSlugDriver;

class IdSlugDriver extends IdWithTransliteratedSlugDriver
{
    public function toSlug(AbstractModel $instance): string
    {
        return (string) $instance->id;
    }
}
