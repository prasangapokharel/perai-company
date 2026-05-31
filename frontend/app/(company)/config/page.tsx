"use client";

import React, { useEffect, useState } from "react";
import { useRouter } from "next/navigation";
import { SettingsForm, FinetuneSettings } from "@/components/finetune/settingsForm";
import { PromptPreview } from "@/components/finetune/promptPreview";
import { ArrowLeft, AlertCircle, Settings } from "lucide-react";
import Link from "next/link";

export default function CompanyConfigPage() {
  const router = useRouter();
  const [companyData, setCompanyData] = useState<any>(null);
  const [apiKey, setApiKey] = useState<string>("");
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string>("");
  const [successMessage, setSuccessMessage] = useState<string>("");
  const [settings, setSettings] = useState<FinetuneSettings>({
    language: "english",
    tone: "formal",
    max_tokens: 1000,
  });
  const [promptPreview, setPromptPreview] = useState<string>("");
  const [previewMetadata, setPreviewMetadata] = useState<any>(null);

  useEffect(() => {
    const loadData = async () => {
      try {
        const storedCompanyId = localStorage.getItem("companyId");
        const storedApiKey = localStorage.getItem("apiKey");
        const storedCompanyData = localStorage.getItem("companyData");

        if (!storedCompanyId || !storedApiKey) {
          setError("No company data found. Please log in first.");
          setIsLoading(false);
          return;
        }

        const parsedData = storedCompanyData ? JSON.parse(storedCompanyData) : {};
        setCompanyData({
          id: storedCompanyId,
          name: parsedData.name || "Your Company",
          category: parsedData.category || "Service Provider",
          website: parsedData.website || "https://example.com",
        });
        setApiKey(storedApiKey);

        // Fetch current settings
        const settingsResponse = await fetch(
          `/api/v1/company/${storedCompanyId}/settings`,
          {
            headers: {
              "X-API-Key": storedApiKey,
            },
          }
        );

        if (settingsResponse.ok) {
          const settingsData = await settingsResponse.json();
          const newSettings = {
            language: settingsData.language,
            tone: settingsData.tone,
            max_tokens: settingsData.max_tokens,
          };
          setSettings(newSettings);
          
          // Generate preview
          await generatePreview(storedCompanyId, storedApiKey, parsedData, newSettings);
        }
      } catch (err) {
        console.error("Failed to load data:", err);
        setError("Failed to load configuration");
      } finally {
        setIsLoading(false);
      }
    };

    loadData();
  }, []);

  const generatePreview = async (
    companyId: string,
    apiKeyVal: string,
    company: any,
    currentSettings: FinetuneSettings
  ) => {
    try {
      const response = await fetch(
        `/api/v1/company/${companyId}/prompt/preview`,
        {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
            "X-API-Key": apiKeyVal,
          },
          body: JSON.stringify({
            tone: currentSettings.tone,
            language: currentSettings.language,
            max_tokens: currentSettings.max_tokens,
            company_name: company.name,
            category: company.category,
            website: company.website,
          }),
        }
      );

      if (response.ok) {
        const data = await response.json();
        setPromptPreview(data.prompt);
        setPreviewMetadata(data.metadata);
      }
    } catch (err) {
      console.error("Failed to generate preview:", err);
    }
  };

  const handleSettingsSave = async (newSettings: FinetuneSettings) => {
    try {
      const response = await fetch(
        `/api/v1/company/${companyData.id}/settings`,
        {
          method: "PUT",
          headers: {
            "Content-Type": "application/json",
            "X-API-Key": apiKey,
          },
          body: JSON.stringify(newSettings),
        }
      );

      if (!response.ok) {
        throw new Error("Failed to save settings");
      }

      setSettings(newSettings);
      await generatePreview(companyData.id, apiKey, companyData, newSettings);
      setSuccessMessage(
        `Settings updated successfully! ${newSettings.tone} tone, ${newSettings.language} language.`
      );
      setTimeout(() => setSuccessMessage(""), 5000);
    } catch (err) {
      const message = err instanceof Error ? err.message : "Failed to save settings";
      setError(message);
      setTimeout(() => setError(""), 5000);
    }
  };

  if (isLoading) {
    return (
      <div className="flex items-center justify-center min-h-screen bg-gray-50 dark:bg-gray-900">
        <div className="animate-spin inline-block h-8 w-8 border-4 border-blue-500 border-r-transparent rounded-full"></div>
      </div>
    );
  }

  if (error || !companyData || !apiKey) {
    return (
      <div className="min-h-screen bg-gray-50 dark:bg-gray-900 p-6">
        <Link
          href="/dashboard"
          className="inline-flex items-center gap-2 text-blue-600 dark:text-blue-400 hover:underline mb-6"
        >
          <ArrowLeft className="h-4 w-4" />
          Back to Dashboard
        </Link>

        <div className="max-w-md bg-red-50 dark:bg-red-950 border border-red-200 dark:border-red-800 rounded-lg p-6">
          <div className="flex gap-3">
            <AlertCircle className="h-5 w-5 text-red-600 dark:text-red-400 flex-shrink-0 mt-0.5" />
            <div>
              <h2 className="font-semibold text-red-900 dark:text-red-200">
                Error Loading Configuration
              </h2>
              <p className="text-sm text-red-700 dark:text-red-300 mt-2">
                {error || "Failed to load company data. Please try logging in again."}
              </p>
            </div>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50 dark:bg-gray-900">
      {/* Header */}
      <div className="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 sticky top-0 z-10">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
          <div className="flex items-center justify-between">
            <Link
              href="/dashboard"
              className="inline-flex items-center gap-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100"
            >
              <ArrowLeft className="h-4 w-4" />
              Back
            </Link>
            <h1 className="text-lg font-semibold text-gray-900 dark:text-gray-100">
              {companyData.name} - Configuration
            </h1>
            <div className="w-16" /> {/* Spacer for alignment */}
          </div>
        </div>
      </div>

      {/* Main Content */}
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {/* Success Message */}
        {successMessage && (
          <div className="mb-6 bg-green-50 dark:bg-green-950 border border-green-200 dark:border-green-800 rounded-lg p-4 text-green-700 dark:text-green-200">
            {successMessage}
          </div>
        )}

        {/* Error Message */}
        {error && (
          <div className="mb-6 bg-red-50 dark:bg-red-950 border border-red-200 dark:border-red-800 rounded-lg p-4 text-red-700 dark:text-red-200">
            {error}
          </div>
        )}

        {/* Content Grid */}
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
          {/* Left Column - Settings Form */}
          <div className="lg:col-span-1">
            <div className="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
              <div className="flex items-center gap-3 mb-6">
                <div className="p-2 bg-blue-100 dark:bg-blue-900 rounded-lg">
                  <Settings className="h-5 w-5 text-blue-600 dark:text-blue-400" />
                </div>
                <h2 className="text-lg font-semibold text-gray-900 dark:text-gray-100">
                  Settings
                </h2>
              </div>

              <SettingsForm
                initialSettings={settings}
                onSettingsSave={handleSettingsSave}
              />
            </div>
          </div>

          {/* Right Column - Preview */}
          <div className="lg:col-span-2">
            <div className="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6 sticky top-24">
              <PromptPreview
                prompt={promptPreview}
                metadata={previewMetadata}
              />
            </div>
          </div>
        </div>

        {/* Info Section */}
        <div className="mt-8 bg-blue-50 dark:bg-blue-950 border border-blue-200 dark:border-blue-800 rounded-lg p-6">
          <h3 className="font-semibold text-blue-900 dark:text-blue-200 mb-2">
            How Configuration Works
          </h3>
          <ul className="space-y-2 text-sm text-blue-800 dark:text-blue-300">
            <li>
              • <strong>Language:</strong> Choose the primary language for AI responses
            </li>
            <li>
              • <strong>Tone:</strong> Set the communication style (formal, casual, friendly, professional)
            </li>
            <li>
              • <strong>Max Tokens:</strong> Control response length (100-4000 tokens)
            </li>
            <li>
              • <strong>Preview:</strong> See how your settings affect the system prompt in real-time
            </li>
          </ul>
        </div>
      </div>
    </div>
  );
}
