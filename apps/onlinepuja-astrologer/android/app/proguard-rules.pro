# Flutter Wrapper ProGuard Rules
-keep class io.flutter.app.** { *; }
-keep class io.flutter.plugin.**  { *; }
-keep class io.flutter.util.**  { *; }
-keep class io.flutter.view.**  { *; }
-keep class io.flutter.** { *; }
-keep class io.flutter.plugins.** { *; }

# Prevent Flutter entrypoint and engine methods from being stripped
-keep class * implements io.flutter.plugin.common.MethodChannel$MethodCallHandler { *; }
-keep class * implements io.flutter.plugin.common.EventChannel$StreamHandler { *; }

# LiveKit & WebRTC (Consultation Calls)
-keep class com.cloudwebrtc.webrtc.** { *; }
-keep class org.webrtc.** { *; }
-dontwarn org.webrtc.**
-keep class io.livekit.android.** { *; }
-dontwarn io.livekit.android.**

# Firebase & Push Notifications
-keepattributes *Annotation*
-keepattributes SourceFile,LineNumberTable
-keep public class * extends java.lang.Exception
-keep class com.google.firebase.** { *; }
-dontwarn com.google.firebase.**

# Gson / JSON Serialization
-keepattributes Signature
-keepclassmembers class * {
    @com.google.gson.annotations.SerializedName <fields>;
}
-keep class com.google.gson.** { *; }

# AndroidX Core & Window Insets
-keep class androidx.core.view.** { *; }
-keep class androidx.window.** { *; }

# Security / Crypto / Audio
-dontwarn javax.annotation.**
-dontwarn org.conscrypt.**

# Flutter Play Store Split / Deferred Components (Optional references)
-dontwarn com.google.android.play.core.splitcompat.**
-dontwarn com.google.android.play.core.splitinstall.**
-dontwarn com.google.android.play.core.tasks.**
-dontwarn com.google.android.play.core.**

# Suppress all general missing references in third-party SDKs
-ignorewarnings
