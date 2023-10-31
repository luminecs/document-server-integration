/**
 *
 * (c) Copyright Ascensio System SIA 2023
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

package com.onlyoffice.integration.controllers;

import com.fasterxml.jackson.databind.ObjectMapper;
import com.onlyoffice.integration.documentserver.managers.history.HistoryManager;
import com.onlyoffice.integration.documentserver.managers.jwt.JwtManager;
import com.onlyoffice.integration.documentserver.models.enums.Action;
import com.onlyoffice.integration.documentserver.models.enums.Type;
import com.onlyoffice.integration.documentserver.models.filemodel.FileModel;
import com.onlyoffice.integration.documentserver.storage.FileStorageMutator;
import com.onlyoffice.integration.documentserver.storage.FileStoragePathBuilder;
import com.onlyoffice.integration.dto.Mentions;
import com.onlyoffice.integration.entities.User;
import com.onlyoffice.integration.services.UserServices;
import com.onlyoffice.integration.services.configurers.FileConfigurer;
import com.onlyoffice.integration.services.configurers.wrappers.DefaultFileWrapper;
import lombok.SneakyThrows;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.beans.factory.annotation.Value;
import org.springframework.stereotype.Controller;
import org.springframework.ui.Model;
import org.springframework.web.bind.annotation.CrossOrigin;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.RequestParam;

import java.io.InputStream;
import java.net.URI;
import java.net.http.HttpClient;
import java.net.http.HttpRequest;
import java.net.http.HttpResponse;
import java.nio.file.Files;
import java.nio.file.Path;
import java.nio.file.StandardCopyOption;
import java.util.ArrayList;
import java.util.HashMap;
import java.util.Map;
import java.util.List;
import java.util.Optional;

import static com.onlyoffice.integration.documentserver.util.Constants.ANONYMOUS_USER_ID;

@CrossOrigin("*")
@Controller
public class HGController {

    @Value("${files.docservice.url.site}")
    private String docserviceSite;

    @Value("${files.docservice.url.api}")
    private String docserviceApiUrl;

    @Value("${files.docservice.languages}")
    private String langs;

    @Autowired
    private FileStoragePathBuilder storagePathBuilder;

    @Autowired
    private JwtManager jwtManager;

    @Autowired
    private UserServices userService;

    @Autowired
    private HistoryManager historyManager;

    @Autowired
    private ObjectMapper objectMapper;

    @Autowired
    private FileConfigurer<DefaultFileWrapper> fileConfigurer;
    @Autowired
    private FileStorageMutator storageMutator;

    @GetMapping("/preview")
    public String preview(@RequestParam(value = "src", required = false) final String src,
                          @RequestParam(value = "actionLink", required = false) final String actionLink,
                          @RequestParam(value = "directUrl", required = false,
                                  defaultValue = "false") final Boolean directUrl,
                          final Model model) {
        if (src == null || src.isEmpty()) {
            model.addAttribute("info", "请提供Office文件地址");
            return "info.html";
        }

        String fileName = "";
        String typeParam = "embedded";
        String actionParam = "embedded";
        Action action = Action.valueOf(actionParam);
        Type type = Type.valueOf(typeParam);
        String uid = "1";

        Optional<User> optionalUser = userService.findUserById(Integer.parseInt(uid));
        if (optionalUser.isEmpty()) {
            model.addAttribute("info", "数据库异常，未找到默认用户");
            return "info.html";
        }

        User user = optionalUser.get();

        // download file from "src"
        try {
            // https://tea.texas.gov/system/files/2021-22-SY-Stopgap-COVID-Test-Allocations.xlsx
            String hostAddress = storagePathBuilder.getStorageLocation();
            fileName = downloadFile(src, hostAddress);
            storageMutator.createMeta(fileName, uid, user.getName());
        } catch (Exception e) {
            model.addAttribute("info", e.getMessage());
            return "info.html";
        }


        // get file model with the default file parameters
        FileModel fileModel = fileConfigurer.getFileModel(
                DefaultFileWrapper
                        .builder()
                        .fileName(fileName)
                        .type(type)
                        .lang("zh-CN")
                        .action(action)
                        .user(user)
                        .actionData(actionLink)
                        .isEnableDirectUrl(directUrl)
                        .build()
        );

        // add attributes to the specified model
        // add file model with the default parameters to the original model
        model.addAttribute("model", fileModel);

        // get file history and add it to the model
        model.addAttribute("fileHistory", historyManager.getHistory(fileModel.getDocument()));

        // create the document service api URL and add it to the model
        model.addAttribute("docserviceApiUrl", docserviceSite + docserviceApiUrl);

        // get an image and add it to the model
        model.addAttribute("dataInsertImage", getInsertImage(directUrl));

        // get a document for comparison and add it to the model
        model.addAttribute("dataCompareFile", getCompareFile(directUrl));

        // get recipients data for mail merging and add it to the model
        model.addAttribute("dataMailMergeRecipients", getMailMerge(directUrl));

        // get user data for mentions and add it to the model
        model.addAttribute("usersForMentions", getUserMentions(uid));
        return "editor.html";
    }


    private List<Mentions> getUserMentions(final String uid) {  // get user data for mentions
        List<Mentions> usersForMentions = new ArrayList<>();
        if (uid != null && !uid.equals("4")) {
            List<User> list = userService.findAll();
            for (User u : list) {
                if (u.getId() != Integer.parseInt(uid) && u.getId() != ANONYMOUS_USER_ID) {

                    // user data includes user names and emails
                    usersForMentions.add(new Mentions(u.getName(), u.getEmail()));
                }
            }
        }

        return usersForMentions;
    }

    @SneakyThrows
    private String getInsertImage(final Boolean directUrl) {  // get an image that will be inserted into the document
        Map<String, Object> dataInsertImage = new HashMap<>();
        dataInsertImage.put("fileType", "png");
        dataInsertImage.put("url", storagePathBuilder.getServerUrl(true) + "/css/img/logo.png");
        if (directUrl) {
            dataInsertImage.put("directUrl", storagePathBuilder
                    .getServerUrl(false) + "/css/img/logo.png");
        }

        // check if the document token is enabled
        if (jwtManager.tokenEnabled()) {

            // create token from the dataInsertImage object
            dataInsertImage.put("token", jwtManager.createToken(dataInsertImage));
        }

        return objectMapper.writeValueAsString(dataInsertImage)
                .substring(1, objectMapper.writeValueAsString(dataInsertImage).length() - 1);
    }

    // get a document that will be compared with the current document
    @SneakyThrows
    private String getCompareFile(final Boolean directUrl) {
        Map<String, Object> dataCompareFile = new HashMap<>();
        dataCompareFile.put("fileType", "docx");
        dataCompareFile.put("url", storagePathBuilder.getServerUrl(true) + "/assets?name=sample.docx");
        if (directUrl) {
            dataCompareFile.put("directUrl", storagePathBuilder
                    .getServerUrl(false) + "/assets?name=sample.docx");
        }

        // check if the document token is enabled
        if (jwtManager.tokenEnabled()) {

            // create token from the dataCompareFile object
            dataCompareFile.put("token", jwtManager.createToken(dataCompareFile));
        }

        return objectMapper.writeValueAsString(dataCompareFile);
    }

    @SneakyThrows
    private String getMailMerge(final Boolean directUrl) {
        Map<String, Object> dataMailMergeRecipients = new HashMap<>();  // get recipients data for mail merging
        dataMailMergeRecipients.put("fileType", "csv");
        dataMailMergeRecipients.put("url", storagePathBuilder.getServerUrl(true) + "/csv");
        if (directUrl) {
            dataMailMergeRecipients.put("directUrl",
                    storagePathBuilder.getServerUrl(false) + "/csv");
        }

        // check if the document token is enabled
        if (jwtManager.tokenEnabled()) {

            // create token from the dataMailMergeRecipients object
            dataMailMergeRecipients.put("token", jwtManager.createToken(dataMailMergeRecipients));
        }

        return objectMapper.writeValueAsString(dataMailMergeRecipients);
    }

    public String downloadFile(final String fileUrl, final String outputFilePath) throws Exception {
        // http://192.168.60.59:4000/preview?src=https://www.inbreak.net/toolss/xcon/Attacking%20Java%20Web.pptx
        // http://192.168.60.59:4000/preview?src=http://www.dstang.com/java/ppt/2.ppt
        // http://192.168.60.59:4000/preview
        // ?src=http://software.henu.edu.cn/__local/2/B2/4E/A03E010870E2CA31ACFA0FE4488_2D7506D5_2B0C.xlsx
        // http://192.168.60.59:4000/preview?src=http://125.222.144.159/_uploadfile/201932713225.xls
        // http://192.168.60.59:4000/preview?src=http://cs.ujs.edu.cn/JavaProgrammingCH.doc
        // http://192.168.60.59:4000/preview
        // ?src=https://rw.scau.edu.cn/_upload/article/files/f7/97
        // /53d29e114eddbc02bb22d187b7e9/988a1058-b18c-4593-91af-c2470ea30d00.docx
        String filename = fileUrl.substring(fileUrl.lastIndexOf('/') + 1);
        String encodedFileName = filename.replace(" ", "%20");
        String fileRealUrl = fileUrl.replace("/" + filename, "/" + encodedFileName);

        HttpRequest request = HttpRequest.newBuilder()
                .uri(new URI(fileRealUrl))
                .build();
        HttpClient client = HttpClient.newHttpClient();

        HttpResponse<InputStream> response = client.send(request, HttpResponse.BodyHandlers.ofInputStream());

        if (!filename.contains(".")) {
            throw new RuntimeException("Office文件网址必须携带Office文件后缀");
        }

        String filepath = outputFilePath + filename;
        try (InputStream stream = response.body()) {
            Files.copy(stream, Path.of(filepath), StandardCopyOption.REPLACE_EXISTING);
        }
        return filename;
    }

}
