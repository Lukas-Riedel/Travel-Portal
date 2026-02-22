plugins {
    alias(libs.plugins.android.application)
    alias(libs.plugins.kotlin.android)
    alias(libs.plugins.kotlin.compose)
    id("org.jetbrains.kotlin.plugin.parcelize")
}

android {
    namespace = "cz.lriedel.bridgex"
    compileSdk = 36

    lint {
        disable += "NullSafeMutableLiveData"
    }

    defaultConfig {
        applicationId = "cz.lriedel.bridgex"
        minSdk = 34
        targetSdk = 36
        versionCode = System.getenv("VERSION_TAG")?.toInt() ?: 1
        versionName = "1.0.${System.getenv("VERSION_TAG") ?: "1"}"

        testInstrumentationRunner = "androidx.test.runner.AndroidJUnitRunner"

        val portalUrl = (project.findProperty("PORTAL_BASE_URL") ?: System.getenv("PORTAL_BASE_URL")) as String
        val iamUrl = (project.findProperty("IAM_BASE_URL") ?: System.getenv("IAM_BASE_URL")) as String
        val iamAppClientId = (project.findProperty("IAM_APP_CLIENT_ID") ?: System.getenv("IAM_APP_CLIENT_ID")) as String
        val coreUrl = (project.findProperty("CORE_BASE_URL") ?: System.getenv("CORE_BASE_URL")) as String
        val firebaseApiKey = (project.findProperty("FIREBASE_API_KEY") ?: System.getenv("FIREBASE_API_KEY")) as String
        val firebaseAuthDomain = (project.findProperty("FIREBASE_AUTH_DOMAIN") ?: System.getenv("FIREBASE_AUTH_DOMAIN")) as String
        val firebaseProjectId = (project.findProperty("FIREBASE_PROJECT_ID") ?: System.getenv("FIREBASE_PROJECT_ID")) as String
        val firebaseStorageBucket = (project.findProperty("FIREBASE_STORAGE_BUCKET") ?: System.getenv("FIREBASE_STORAGE_BUCKET")) as String
        val firebaseMessagingSenderId = (project.findProperty("FIREBASE_MESSAGING_SENDER_ID") ?: System.getenv("FIREBASE_MESSAGING_SENDER_ID")) as String
        val firebaseAppId = (project.findProperty("FIREBASE_APP_ID") ?: System.getenv("FIREBASE_APP_ID")) as String
        val firebaseMeasurementId = (project.findProperty("FIREBASE_MEASUREMENT_ID") ?: System.getenv("FIREBASE_MEASUREMENT_ID")) as String
        val firebaseVapidKey = (project.findProperty("FIREBASE_VAPID_KEY") ?: System.getenv("FIREBASE_VAPID_KEY")) as String

        buildConfigField("String", "PORTAL_BASE_URL", "\"$portalUrl/\"")
        buildConfigField("String", "IAM_BASE_URL", "\"$iamUrl/\"")
        buildConfigField("String", "IAM_APP_CLIENT_ID", "\"$iamAppClientId\"")
        buildConfigField("String", "CORE_BASE_URL", "\"$coreUrl/\"")
        buildConfigField("String", "FIREBASE_API_KEY", "\"$firebaseApiKey\"")
        buildConfigField("String", "FIREBASE_AUTH_DOMAIN", "\"$firebaseAuthDomain\"")
        buildConfigField("String", "FIREBASE_PROJECT_ID", "\"$firebaseProjectId\"")
        buildConfigField("String", "FIREBASE_STORAGE_BUCKET", "\"$firebaseStorageBucket\"")
        buildConfigField("String", "FIREBASE_MESSAGING_SENDER_ID", "\"$firebaseMessagingSenderId\"")
        buildConfigField("String", "FIREBASE_APP_ID", "\"$firebaseAppId\"")
        buildConfigField("String", "FIREBASE_MEASUREMENT_ID", "\"$firebaseMeasurementId\"")
        buildConfigField("String", "FIREBASE_VAPID_KEY", "\"$firebaseVapidKey\"")
    }

    signingConfigs {
        create("release") {
            storeFile = file((project.findProperty("KEYSTORE_FILE") ?: System.getenv("KEYSTORE_FILE")) as String)
            storePassword = (project.findProperty("KEYSTORE_PASSWORD") ?: System.getenv("KEYSTORE_PASSWORD")) as String
            keyAlias = (project.findProperty("KEY_ALIAS") ?: System.getenv("KEY_ALIAS")) as String
            keyPassword = (project.findProperty("KEY_PASSWORD") ?: System.getenv("KEY_PASSWORD")) as String
        }
    }

    buildTypes {
        getByName("release") {
            signingConfig = signingConfigs.getByName("release")
            isMinifyEnabled = false
            proguardFiles(
                getDefaultProguardFile("proguard-android-optimize.txt"),
                "proguard-rules.pro"
            )
        }
    }

    compileOptions {
        sourceCompatibility = JavaVersion.VERSION_21
        targetCompatibility = JavaVersion.VERSION_21
    }

    kotlinOptions {
        jvmTarget = "21"
    }

    buildFeatures {
        buildConfig = true
        compose = true
    }
}

dependencies {
    implementation(libs.androidx.core.ktx)
    implementation(libs.androidx.lifecycle.runtime.ktx)
    implementation(libs.androidx.activity.compose)
    implementation(platform(libs.androidx.compose.bom))
    implementation(libs.androidx.ui)
    implementation(libs.androidx.ui.graphics)
    implementation(libs.androidx.ui.tooling.preview)
    implementation(libs.androidx.material3)
    implementation(libs.androidx.appcompat)
    implementation(libs.androidx.material)
    implementation(libs.androidx.security.crypto)
    implementation(libs.retrofit)
    implementation(libs.retrofit.gson)
    implementation(platform(libs.firebase.bom))
    implementation(libs.firebase.common)
    implementation(libs.firebase.messaging)
    implementation(libs.androidx.health.connect)
    implementation(libs.kotlin.parcelize.runtime)
    implementation(libs.location)
}