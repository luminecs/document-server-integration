/**
 *
 * (c) Copyright Ascensio System SIA 2021
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 */

package com.onlyoffice.integration.mappers;

import com.onlyoffice.integration.entities.User;
import org.modelmapper.ModelMapper;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.context.annotation.Primary;
import org.springframework.stereotype.Component;

import javax.annotation.PostConstruct;

@Component
@Primary
public class UsersMapper extends AbstractMapper<User, com.onlyoffice.integration.documentserver.models.filemodel.User> {
    @Autowired
    private ModelMapper mapper;

    public UsersMapper(){
        super(com.onlyoffice.integration.documentserver.models.filemodel.User.class);
    }

    @PostConstruct
    public void configure() {
        mapper.createTypeMap(User.class, com.onlyoffice.integration.documentserver.models.filemodel.User.class)
                .setPostConverter(modelConverter());
    }

    @Override
    public void handleSpecificFields(User source, com.onlyoffice.integration.documentserver.models.filemodel.User destination) {
        destination.setGroup(source.getGroup() != null ? source.getGroup().getName() : null);
    }
}
