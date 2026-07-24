<?php

namespace App\Controller\Admin2;

use Symfony\Component\HttpFoundation\Request;

trait Admin2BulkIdsTrait
{
    /**
     * @return list<int>
     */
    private function parseBulkIds(Request $request): array
    {
        $raw = $request->request->all('ids');
        if (! is_array($raw)) {
            return [];
        }

        $ids = [];
        foreach ($raw as $value) {
            $id = (int) $value;
            if ($id > 0) {
                $ids[] = $id;
            }
        }

        return array_values(array_unique($ids));
    }
}
