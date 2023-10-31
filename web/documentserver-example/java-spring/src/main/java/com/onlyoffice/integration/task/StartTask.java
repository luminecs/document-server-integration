package com.onlyoffice.integration.task;

import org.springframework.beans.factory.annotation.Value;
import org.springframework.boot.CommandLineRunner;
import org.springframework.context.annotation.Bean;
import org.springframework.context.annotation.Configuration;

import java.nio.file.Path;
import java.nio.file.Paths;
import java.util.Calendar;
import java.util.Date;
import java.util.Timer;

@Configuration
public class StartTask {

    @Value("${task.clear.documents.time}") // 04:00
    private String scheduleClearTaskTime;

    @Bean
    public CommandLineRunner scheduleTask() {
        return args -> {
            // 设置清空目录任务
            Path directoryToClear = Paths.get(System.getProperty("user.dir") + "/documents/");
            ClearDirectoryTask task = new ClearDirectoryTask(directoryToClear);

            int hour = 4;
            int minute = 0;
            String[] split = scheduleClearTaskTime.split(":");
            if (split.length == 2) {
                String s1 = split[0];
                String s2 = split[1];
                if (s1 != null && !s1.isBlank() && s2 != null && !s2.isBlank()) {
                    try {
                        hour = Integer.parseInt(s1);
                        minute = Integer.parseInt(s2);
                    } catch (Exception e) {
                    }
                }
            }

            // 设置运行任务的时间，例如每天凌晨4点
            Calendar calendar = Calendar.getInstance();
            calendar.set(Calendar.HOUR_OF_DAY, hour);
            calendar.set(Calendar.MINUTE, minute);
            calendar.set(Calendar.SECOND, 0);
            Date firstTime = calendar.getTime();

            // 创建timer，并设置每天凌晨 4点执行任务
            Timer timer = new Timer();
            long period = 24 * 60 * 60 * 1000;
            timer.schedule(task, firstTime, period);
        };
    }
}
