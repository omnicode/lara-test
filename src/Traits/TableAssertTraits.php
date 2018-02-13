<?php

namespace LaraTest\Traits;

use function dd;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use LaraTools\Utility\LaraUtil;

trait TableAssertTraits
{
    public function assertTableHasColumns($table, $columns, $message = '')
    {
        if (!is_array($columns)) {
            $columns = [$columns];
        }

        foreach ($columns as $column) {
            if (empty($message)) {
                $message = sprintf("'%s' table does not have a '%s' column", $table, last(explode('.', $column)));
            }
            $this->assertTrue(LaraUtil::hasColumn($table, $column), $message);
        }

    }
}
