@props(['columns', 'rows' => 3])

@for ($row = 0; $row < $rows; $row++)
    <tr class="table-skeleton-row" aria-hidden="true">
        @for ($column = 0; $column < $columns; $column++)
            <td><span class="skeleton-block skeleton-line"></span></td>
        @endfor
    </tr>
@endfor
