plugins {
    alias(libs.plugins.android.application)
    alias(libs.plugins.kotlin.android)
    alias(libs.plugins.kotlin.compose)
}

android {
    namespace = "cz.lriedel.bridgex"
    compileSdk = 36

    defaultConfig {
        applicationId = "cz.lriedel.bridgex"
        minSdk = 27
        targetSdk = 36
        versionCode = System.getenv("VERSION_TAG")?.toInt() ?: 1
        versionName = "1.0.${System.getenv("VERSION_TAG") ?: "1"}"

        testInstrumentationRunner = "androidx.test.runner.AndroidJUnitRunner"

        val portalUrl = (project.findProperty("PORTAL_BASE_URL") ?: System.getenv("PORTAL_BASE_URL")) as String
        buildConfigField("String", "PORTAL_BASE_URL", "\"$portalUrl\"")
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
    testImplementation(libs.junit)
    androidTestImplementation(libs.androidx.junit)
    androidTestImplementation(libs.androidx.espresso.core)
    androidTestImplementation(platform(libs.androidx.compose.bom))
    androidTestImplementation(libs.androidx.ui.test.junit4)
    debugImplementation(libs.androidx.ui.tooling)
    debugImplementation(libs.androidx.ui.test.manifest)
}