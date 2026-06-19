<?php

namespace App\Domain\Branch;

enum BranchResolutionStrategy
{
    /** The model carries its own branch_id column. */
    case Direct;

    /** Branch is reached via warehouse->branch_id (nullable). */
    case Warehouse;

    /** The type has no branch concept (intentionally unscopable). */
    case None;
}
