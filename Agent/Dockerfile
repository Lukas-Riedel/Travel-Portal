FROM maven:3-eclipse-temurin-17-alpine AS builder
WORKDIR /build
ARG SERVICE_CORE_URLARG USERNAME
ARG PASSWORD
COPY Agent/pom.xml .
RUN mvn dependency:go-offline -B
COPY Agent/src ./src
RUN mvn clean package -DskipTests
FROM eclipse-temurin:17-jre-jammy
WORKDIR /app
RUN apt-get update && apt-get install -y \
    libjpeg-turbo-progs \
    && rm -rf /var/lib/apt/lists/*
COPY --from=builder /build/target/*.jar agent.jar
RUN chmod -R 777 /app
ENTRYPOINT ["java", "-jar", "agent.jar"]