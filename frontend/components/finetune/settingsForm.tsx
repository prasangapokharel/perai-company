"use client";

import React, { useState, useCallback } from "react";
import { Settings, Save, RotateCcw } from "lucide-react";
import { cn } from "@/lib/utils";

export interface FinetuneSettings {
  language: "english" | "nepali";
  tone: "formal" | "casual" | "friendly" | "professional";
  max_tokens: number;
}

export interface SettingsFormProps {
  initialSettings?: Partial<FinetuneSettings>;
  onSettingsSave: (settings: FinetuneSettings) => Promise<void>;
  disabled?: boolean;
  isLoading?: boolean;
}

const LANGUAGE_OPTIONS = [
  { value: "english", label: "English", description: "Clear and professional English" },
  { value: "nepali", label: "नेपाली (Nepali)", description: "नेपाली भाषा (Nepali Language)" },
];

const TONE_OPTIONS = [
  {
    value: "formal",
    label: "Formal",
    description: "Professional, structured, and business-appropriate",
  },
  {
    value: "casual",
    label: "Casual",
    description: "Conversational, friendly, and everyday language",
  },
  {
    value: "friendly",
    label: "Friendly",
    description: "Warm, welcoming, and empathetic",
  },
  {
    value: "professional",
    label: "Professional",
    description: "Expert, authoritative, and knowledgeable",
  },
];

export const SettingsForm: React.FC<SettingsFormProps> = ({
  initialSettings = {
    language: "english",
    tone: "formal",
    max_tokens: 1000,
  },
  onSettingsSave,
  disabled = false,
  isLoading = false,
}) => {
  const [settings, setSettings] = useState<FinetuneSettings>({
    language: (initialSettings.language || "english") as "english" | "nepali",
    tone: (initialSettings.tone || "formal") as "formal" | "casual" | "friendly" | "professional",
    max_tokens: initialSettings.max_tokens || 1000,
  });

  const [hasChanges, setHasChanges] = useState(false);
  const [isSaving, setIsSaving] = useState(false);
  const [saveMessage, setSaveMessage] = useState<{ type: "success" | "error"; text: string } | null>(null);

  const handleLanguageChange = useCallback((language: "english" | "nepali") => {
    setSettings((prev) => ({ ...prev, language }));
    setHasChanges(true);
    setSaveMessage(null);
  }, []);

  const handleToneChange = useCallback((tone: "formal" | "casual" | "friendly" | "professional") => {
    setSettings((prev) => ({ ...prev, tone }));
    setHasChanges(true);
    setSaveMessage(null);
  }, []);

  const handleTokensChange = useCallback((value: number) => {
    const clamped = Math.max(100, Math.min(4000, value));
    setSettings((prev) => ({ ...prev, max_tokens: clamped }));
    setHasChanges(true);
    setSaveMessage(null);
  }, []);

  const handleSave = useCallback(async () => {
    setIsSaving(true);
    try {
      await onSettingsSave(settings);
      setHasChanges(false);
      setSaveMessage({ type: "success", text: "Settings saved successfully!" });
    } catch (error) {
      setSaveMessage({
        type: "error",
        text: error instanceof Error ? error.message : "Failed to save settings",
      });
    } finally {
      setIsSaving(false);
    }
  }, [settings, onSettingsSave]);

  const handleReset = useCallback(() => {
    setSettings({
      language: (initialSettings.language || "english") as "english" | "nepali",
      tone: (initialSettings.tone || "formal") as "formal" | "casual" | "friendly" | "professional",
      max_tokens: initialSettings.max_tokens || 1000,
    });
    setHasChanges(false);
    setSaveMessage(null);
  }, [initialSettings]);

  return (
    <div className="w-full space-y-6">
      {/* Header */}
      <div className="flex items-center gap-2">
        <Settings className="h-5 w-5 text-gray-700 dark:text-gray-300" />
        <h2 className="text-xl font-semibold text-gray-900 dark:text-gray-100">
          Finetune Settings
        </h2>
      </div>

      {/* Language Selection */}
      <div className="space-y-3">
        <label className="block text-sm font-medium text-gray-900 dark:text-gray-100">
          Response Language
        </label>
        <div className="grid grid-cols-1 gap-3">
          {LANGUAGE_OPTIONS.map((option) => (
            <button
              key={option.value}
              onClick={() => handleLanguageChange(option.value as "english" | "nepali")}
              disabled={disabled || isLoading}
              className={cn(
                "p-4 rounded-lg border-2 transition-all text-left",
                settings.language === option.value
                  ? "border-blue-500 bg-blue-50 dark:bg-blue-950"
                  : "border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 hover:border-gray-300 dark:hover:border-gray-600",
                disabled || isLoading ? "opacity-50 cursor-not-allowed" : "cursor-pointer"
              )}
            >
              <div className="flex items-center">
                <div
                  className={cn(
                    "w-4 h-4 rounded-full border-2 mr-3",
                    settings.language === option.value
                      ? "border-blue-500 bg-blue-500"
                      : "border-gray-300 dark:border-gray-600"
                  )}
                />
                <div className="flex-1">
                  <p className="font-medium text-gray-900 dark:text-gray-100">
                    {option.label}
                  </p>
                  <p className="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                    {option.description}
                  </p>
                </div>
              </div>
            </button>
          ))}
        </div>
      </div>

      {/* Tone Selection */}
      <div className="space-y-3">
        <label className="block text-sm font-medium text-gray-900 dark:text-gray-100">
          Response Tone
        </label>
        <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
          {TONE_OPTIONS.map((option) => (
            <button
              key={option.value}
              onClick={() => handleToneChange(option.value as any)}
              disabled={disabled || isLoading}
              className={cn(
                "p-4 rounded-lg border-2 transition-all text-left",
                settings.tone === option.value
                  ? "border-blue-500 bg-blue-50 dark:bg-blue-950"
                  : "border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 hover:border-gray-300 dark:hover:border-gray-600",
                disabled || isLoading ? "opacity-50 cursor-not-allowed" : "cursor-pointer"
              )}
            >
              <div className="flex items-center">
                <div
                  className={cn(
                    "w-4 h-4 rounded-full border-2 mr-3 flex-shrink-0",
                    settings.tone === option.value
                      ? "border-blue-500 bg-blue-500"
                      : "border-gray-300 dark:border-gray-600"
                  )}
                />
                <div className="flex-1">
                  <p className="font-medium text-gray-900 dark:text-gray-100">
                    {option.label}
                  </p>
                  <p className="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                    {option.description}
                  </p>
                </div>
              </div>
            </button>
          ))}
        </div>
      </div>

      {/* Max Tokens Slider */}
      <div className="space-y-3">
        <div className="flex items-center justify-between">
          <label className="block text-sm font-medium text-gray-900 dark:text-gray-100">
            Response Token Limit
          </label>
          <span className="text-2xl font-bold text-blue-600 dark:text-blue-400">
            {settings.max_tokens}
          </span>
        </div>
        <div className="space-y-2">
          <input
            type="range"
            min="100"
            max="4000"
            step="100"
            value={settings.max_tokens}
            onChange={(e) => handleTokensChange(parseInt(e.target.value))}
            disabled={disabled || isLoading}
            className={cn(
              "w-full h-2 bg-gray-200 dark:bg-gray-700 rounded-lg appearance-none cursor-pointer",
              disabled || isLoading ? "opacity-50 cursor-not-allowed" : ""
            )}
          />
          <div className="flex justify-between text-xs text-gray-500 dark:text-gray-400">
            <span>100 tokens</span>
            <span>4000 tokens</span>
          </div>
          <p className="text-xs text-gray-500 dark:text-gray-400 mt-2">
            Limits the length of AI responses. Fewer tokens = shorter responses, more tokens = longer, more detailed responses.
          </p>
        </div>
      </div>

      {/* Save Message */}
      {saveMessage && (
        <div
          className={cn(
            "p-4 rounded-lg border",
            saveMessage.type === "success"
              ? "bg-green-50 dark:bg-green-950 border-green-200 dark:border-green-800 text-green-700 dark:text-green-200"
              : "bg-red-50 dark:bg-red-950 border-red-200 dark:border-red-800 text-red-700 dark:text-red-200"
          )}
        >
          {saveMessage.text}
        </div>
      )}

      {/* Action Buttons */}
      <div className="flex gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
        <button
          onClick={handleSave}
          disabled={!hasChanges || disabled || isSaving}
          className={cn(
            "flex items-center gap-2 px-6 py-2 rounded-lg font-medium transition-colors",
            hasChanges && !disabled && !isSaving
              ? "bg-blue-600 text-white hover:bg-blue-700"
              : "bg-gray-200 dark:bg-gray-700 text-gray-500 dark:text-gray-400 cursor-not-allowed"
          )}
        >
          <Save className="h-4 w-4" />
          {isSaving ? "Saving..." : "Save Settings"}
        </button>

        <button
          onClick={handleReset}
          disabled={!hasChanges || disabled || isSaving}
          className={cn(
            "flex items-center gap-2 px-6 py-2 rounded-lg font-medium transition-colors",
            hasChanges && !disabled && !isSaving
              ? "bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-gray-100 hover:bg-gray-300 dark:hover:bg-gray-600"
              : "bg-gray-100 dark:bg-gray-800 text-gray-400 dark:text-gray-500 cursor-not-allowed"
          )}
        >
          <RotateCcw className="h-4 w-4" />
          Reset
        </button>
      </div>
    </div>
  );
};

export default SettingsForm;
