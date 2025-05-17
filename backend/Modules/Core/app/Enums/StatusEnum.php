<?php

namespace Modules\Core\Enums;

enum StatusEnum: string
{
    case OK             = 'common::status.success.ok';
    case INTERNAL_ERROR = 'common::status.errors.internalError';
    case UNAUTHORIZED   = 'common::status.errors.unauthorized';
}
