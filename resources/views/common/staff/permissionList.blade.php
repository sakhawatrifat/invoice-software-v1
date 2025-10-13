@php
	$getCurrentTranslation = getCurrentTranslation();
@endphp

<table class="table table-rounded table-striped border dataTable no-footer gs-7 align-middle permission-table">
    <thead class="table-light">
        <tr>
            <th>{{ $getCurrentTranslation['module'] ?? 'module' }}</th>
            <th>{{ $getCurrentTranslation['permissions'] ?? 'permissions' }}</th>
        </tr>
    </thead>
    <tbody>
        @php
            $userType = 'user';

            // full permission list
            $allPermissions = getPermissionList();

            // decode permissions from DB
            $parentPermissions = $parentPermissions ?? []; // passed from controller
            $userPermissions   = $userPermissions ?? [];

            // filter only permissions available to parent
            $permissions = collect($allPermissions)->map(function ($group) use ($parentPermissions) {
                $group['permissions'] = collect($group['permissions'])
                    ->filter(function ($perm) use ($parentPermissions) {
                        return in_array($perm['key'], $parentPermissions);
                    })
                    ->values()
                    ->toArray();
                return $group;
            })
            ->filter(function ($group) {
                return !empty($group['permissions']); // keep only non-empty groups
            })
            ->values()
            ->toArray();

            $selectedPermissions = $userPermissions;
        @endphp

        @foreach($permissions as $groupIndex => $item)
            @php
                // keys of this group
                $groupKeys = array_column($item['permissions'], 'key');
                // is the whole group checked by user?
                $isGroupChecked = count(array_intersect($groupKeys, $selectedPermissions)) === count($groupKeys);
            @endphp
            <tr class="{{ $groupIndex % 2 == 0 ? 'even' : 'odd' }} {{ $item['for'] ?? '' }}">
                <td>
                    <div class="form-check">
                        <input type="checkbox"
                            class="form-check-input group-checkbox"
                            id="groupCheck{{ $groupIndex }}"
                            data-group="{{ $groupIndex }}"
                            {{ $isGroupChecked ? 'checked' : '' }}>
                        <label class="form-check-label user-select-none" for="groupCheck{{ $groupIndex }}">
                            {{ $getCurrentTranslation[$item['title']] ?? $item['title'] }}
                        </label>
                    </div>
                </td>
                <td>
                    <ul class="list-unstyled mb-0">
                        @foreach($item['permissions'] as $permIndex => $permission)
                            <li>
                                <div class="form-check my-3">
                                    <input type="checkbox"
                                        class="form-check-input permission-checkbox group-{{ $groupIndex }}"
                                        name="permissions[]"
                                        value="{{ $permission['key'] }}"
                                        id="perm{{ $groupIndex }}_{{ $permIndex }}"
                                        {{ in_array($permission['key'], $selectedPermissions) ? 'checked' : '' }}>
                                    <label class="form-check-label user-select-none" for="perm{{ $groupIndex }}_{{ $permIndex }}">
                                        {{ $getCurrentTranslation[$permission['title']] ?? $permission['title'] }}
                                    </label>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </td>
            </tr>
        @endforeach

    </tbody>
</table>
