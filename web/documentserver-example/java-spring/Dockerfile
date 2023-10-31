# 注释原来的，不用compose，直接打镜像
#FROM maven:3.8.1-jdk-11
#COPY . /usr/src/app
#WORKDIR /usr/src/app
#RUN mvn -f /usr/src/app/pom.xml clean

# 使用 OpenJDK 11
FROM openjdk:11-jre-slim
# 设置工作目录
WORKDIR /usr/app
# 将 jar 文件复制到 Docker 镜像中
COPY ./target/integration-1.0.jar /usr/app
# 声明需要暴露的端口
EXPOSE 4000
# 执行应用：docker run -d -p 4000:4000 -v /path/to/your/application.properties:/config/application.properties image-name
ENTRYPOINT ["java", "-Dfile.encoding=utf-8", "-jar", "/usr/app/integration-1.0.jar", "--spring.config.location=/config/application.properties"]


