package com.onlyoffice.integration.task;

import java.io.IOException;
import java.nio.file.*;
import java.nio.file.attribute.BasicFileAttributes;
import java.util.TimerTask;

public class ClearDirectoryTask extends TimerTask {

    private final Path directoryToClear;

    public ClearDirectoryTask(Path directoryToClear) {
        this.directoryToClear = directoryToClear;
    }

    public static void deleteDirectoryRecursively(Path dirPath) throws IOException {
        // Path dirPath = Paths.get(directory);
        Files.walkFileTree(dirPath, new SimpleFileVisitor<>() {
            @Override
            public FileVisitResult visitFile(Path file, BasicFileAttributes attrs) throws IOException {
                Files.delete(file);
                return FileVisitResult.CONTINUE;
            }

            @Override
            public FileVisitResult postVisitDirectory(Path dir, IOException exc) throws IOException {
                Files.delete(dir);
                return FileVisitResult.CONTINUE;
            }
        });
    }

    @Override
    public void run() {
        try {
            deleteDirectoryRecursively(directoryToClear);
        } catch (IOException e) {
            e.printStackTrace();
        }
    }
}
