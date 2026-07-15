<?php

namespace App\Service\Admin2;

use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;

final readonly class Admin2Paginator
{
    public const PER_PAGE_OPTIONS = [15, 25, 50, 100];

    public const DEFAULT_PER_PAGE = 25;

    public function __construct(private PaginatorInterface $paginator)
    {
    }

    public function normalizePerPage(int $perPage): int
    {
        return in_array($perPage, self::PER_PAGE_OPTIONS, true) ? $perPage : self::DEFAULT_PER_PAGE;
    }

    /**
     * @return PaginationInterface<int, mixed>
     */
    public function paginate(mixed $target, int $page, int $perPage): PaginationInterface
    {
        return $this->paginator->paginate(
            $target,
            $page,
            $this->normalizePerPage($perPage),
            [
                'distinct'                     => true,
                'sortFieldParameterName'       => '_knp_sort',
                'sortDirectionParameterName'   => '_knp_direction',
            ],
        );
    }
}
