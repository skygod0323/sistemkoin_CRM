<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2021 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
 *
 * EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * EspoCRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with EspoCRM. If not, see http://www.gnu.org/licenses/.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

namespace Espo\Core\Select\AccessControlFilters;

use Espo\{
    ORM\QueryParams\SelectBuilder as QueryBuilder,
    Core\Select\Filters\AccessControlFilter,
    Core\Select\Helpers\FieldHelper,
    Entities\User,
};

class OnlyTeam implements AccessControlFilter
{
    protected $entityType;

    protected $user;

    protected $fieldHelper;

    public function __construct(string $entityType, User $user, FieldHelper $fieldHelper)
    {
        $this->entityType = $entityType;
        $this->user = $user;
        $this->fieldHelper = $fieldHelper;
    }

    public function apply(QueryBuilder $queryBuilder) : void
    {
        if (!$this->fieldHelper->hasTeamsField()) {
            $queryBuilder->where([
                'id' => null,
            ]);

            return;
        }

        $queryBuilder->distinct();

        $queryBuilder->leftJoin('teams', 'teamsAccess');

        if ($this->fieldHelper->hasAssignedUsersField()) {
            $queryBuilder->leftJoin('assignedUsers', 'assignedUsersAccess');

            $queryBuilder->where([
                'OR' => [
                    'teamsAccess.id' => $this->user->getTeamIdList(),
                    'assignedUsersAccess.id' => $this->user->id,
                ]
            ]);

            return;
        }

        $orGroup = [
            'teamsAccess.id' => $this->user->getTeamIdList(),
        ];

        if ($this->fieldHelper->hasAssignedUserField()) {
            $orGroup['assignedUserId'] = $this->user->id;
        }
        else if ($this->fieldHelper->hasCreatedByField()) {
            $orGroup['createdById'] = $this->user->id;
        }

        $queryBuilder->where([
            'OR' => $orGroup,
        ]);
    }
}
