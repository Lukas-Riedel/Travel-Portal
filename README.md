# Travel Portal 🌍

A sophisticated, fully automated microservices ecosystem designed to manage trips, visited locations, and aviation history. This project is built to eliminate manual overhead while pushing the boundaries of cloud-native engineering.

## 📖 The Evolution & Philosophy

This project is the culmination of a long journey that started with a simple need to visualize my travels.

* **The WordPress Era:** It began on a standard **shared webhosting**. I spent over a year trying to force WordPress into this role, but it just sucked. It was bloated, inflexible, and required endless manual input. 
* **The Hybrid Phase:** After scrapping WordPress, the first version of this project still lived on **shared hosting**. It was a "distributed-lite" setup using external cloud providers for **RabbitMQ** and **Redis**, with data stored in a shared **MySQL** instance. It worked, but it was fragmented and limited.
* **The Kubernetes Era:** To have everything under one roof and fully under my control, I migrated the entire stack into my own **VPS running a Kubernetes cluster**. 

As a developer, I believe in automation. I'm too lazy to write everything manually, so I built a **"self-writing" portal** - a platform where I don't spend time filling out forms. Instead, the portal acts as a **high-end UI and API layer** that orchestrates data from my existing digital footprint.

I’ve always wanted a map of all the places I’ve visited, but as a **child of the digital age**, a paper map on the wall wasn't enough. I wanted it live, interactive, and accessible on the web.

🚀 **Live Version:** [https://lriedel.cz](https://lriedel.cz)

---

## ⚙️ Automated Workflow: How it works

The Travel Portal is "data-driven" in its purest form, leveraging an array of APIs to eliminate manual data entry and provide rich context:

* **The Single Source of Truth:** My **Google Calendar** serves as the primary data carrier. A trip scheduled in the calendar automatically triggers the creation of a new entry.
* **Geospatial Processing:** The system takes location strings from calendar events and uses **Geocoding APIs** to resolve them into precise coordinates for the map engine.
* **Aviation Logbook:** As an **aviation enthusiast**, I track every flight. The system automatically fetches data from **Flightradar24 APIs** to log flight history (routes, aircraft types, mileage) without me lifting a finger.
* **Environmental Context:** For every trip, the system pulls historical and real-time data, such as **weather forecasts** and local time zones, to create a complete record of the experience.
* **Interactive Mapping:** Using the **Google Maps JavaScript API**, the portal renders dynamic markers filterable by **years** or **geographical regions**.
* **AI-Generated Narratives:** The **Gemini API** analyzes all collected metadata to automatically generate rich, human-like text content.
* **Smart Media Handling:** Since Google Photos API calls are "expensive" (quotas/latency), the system implements a caching layer. Selected photos are cached in a local **S3 storage (MinIO)** within the cluster for instant loading.
* **Real-time Notifications**: The system stays proactive. Through integration with **Firebase Cloud Messaging (FCM)**, the portal sends instant push notifications to the mobile device regarding flight updates, successful data synchronizations, or AI processing completions.

---

## 🏗 Microservices Architecture & Security

The system is decoupled into specialized services, utilizing **RabbitMQ** for asynchronous communication and a centralized **Identity and Access Management (IAM)** layer.

### 🔐 Security & Identity
* **Iam (PHP / Keycloak Wrapper):** A dedicated REST API service acting as a security gateway. It interfaces with **Keycloak** for robust authentication.
* **Role-Based Access Control (RBAC)**: Access management is handled through a fine-grained RBAC system. Permissions are defined as specific resource-action pairs, providing consistent authorization across all services.
* **Token-Based Auth:** Every microservice in the cluster must communicate with the **Iam** service to obtain a valid **Bearer Token**. This ensures that the internal communication follows a strict "Security-First" approach.

### 🧱 Specialized Services
* **Portal (React):** A high-performance PWA serving as the main interface. Focused on map visualizations and gallery rendering. By implementing **Service Workers**, the portal supports offline caching and handles **Web Push Notifications** via **FCM**, ensuring real-time updates reach the desktop even when the browser tab is inactive.
* **Core (PHP / REST API):** The central brain. Since PHP is single-threaded, Core includes a **dedicated background worker** that consumes **RabbitMQ** tasks to handle complex API integrations without blocking the API response. **Maintained in PHP for historical reasons** (originally built for shared hosting), it has since been modernized into a cloud-native service.
* **Cortex (Python / AI Worker):** A specialized AI engine for photo evaluation. It has **no public API**; it operates purely as an RMQ consumer.
* **Agent (Java / RabbitMQ Client):** A worker for heavy lifting, such as high-volume image processing, metadata extraction, and massive data synchronization.
* **BridgeX (Android / Native):** A native gateway providing access to **GPS tracking** for precise location history and **Health Connect** integration. It also serves as a specialized container that renders the **Portal via an optimized WebView**. This allows for a seamless mobile experience while acting as a **native FCM consumer** for low-latency system alerts.

### 📉 Storage & Caching Strategy
* **MinIO (S3):** High-speed media caching to bypass Google Photos API limitations.
* **Redis:** Used exclusively for data with specific **TTL (Time To Live)**. No traditional sessions are used; Redis handles the lifecycle of temporary data automatically.
* **PostgreSQL:** The robust relational foundation for all structured business data.

---

## 🛠 DevOps, CI/CD & Reliability

### 📈 Elastic Scaling
To ensure efficiency, the system implements **horizontal autoscaling**. Both the **Core Worker** and **Cortex (AI Worker)** are automatically scaled within the Kubernetes cluster based on the **RabbitMQ queue size**.

### 🛡️ Resilience & Self-Healing
Every microservice is configured with **Liveness and Readiness probes**. This ensures that the Kubernetes orchestrator can automatically restart failing containers and direct traffic only to instances that are fully initialized and healthy.

### 🔄 Automated Backups
Data integrity is critical. During **every deployment**, a GitHub Action triggers an automated backup process:
1.  **PostgreSQL** database is dumped.
2.  **Google Calendar** data is exported.
3.  The resulting backups are automatically uploaded to a secure **Google Drive** folder.

### 📊 Monitoring & Observability
* **Grafana:** Centralized dashboard for tracking system logs and health.
* **GitHub Actions:** Full CI/CD pipeline. Every commit builds and deploys via **Helm charts** into the **Kubernetes (K8s) cluster**.

---

## 📉 Cost Optimization
Operating this many services on a **small, budget-friendly cluster** is an engineering challenge:
* **Resource Tuning:** Strict CPU and Memory limits ensure maximum density without performance degradation.
* **Idle Efficiency:** Lightweight base images and optimized scheduling keep the cloud bill at a minimum. The system only consumes significant power when there is an actual workload in the queue.

---

## ❓ Final Thoughts: The "Why?"

**"Do I really need a microservices architecture, a message broker, and an AI engine just to track my trips?"**

The answer is: **Absolutely not.** A simple spreadsheet or a basic monolithic app would do the job.

**"So why build it this way?"**

Because this project is a testament to engineering for the sake of learning. It’s an over-engineered playground designed to master **K8s, RMQ, Keycloak, and GenAI** in a real-world environment. It’s about solving the problem of "digital laziness" with the most complex and professional tools available.