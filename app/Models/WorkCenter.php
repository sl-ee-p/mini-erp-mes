<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\WorkCenterFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 */
#[Fillable(['code', 'name'])]
class WorkCenter extends Model
{
    /** @use HasFactory<WorkCenterFactory> */
    use HasFactory;
}
