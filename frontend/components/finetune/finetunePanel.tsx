"use client";

import React, { useState, useCallback, useEffect } from "react";
import { Zap, AlertCircle } from "lucide-react";
import { DragAndDrop } from "./dragandDrop";
import { SettingsForm, FinetuneSettings } from "./settingsForm";
import { PromptPreview } from "./promptPreview";
import { cn } from "@/lib/utils";

export interface FinetunePanel Props {
  companyId: string;
  companyName: string;
  companyCategory: string;
  companyWebsite: string;
  apiKey: string;
  onSuccess?: (message: string) => void;
  onError?: (error: string) => void;
}

export const FinetunePanel: React.FC<FinetunePanel Props> = ({
  companyId,
  companyName,
  companyCategory,
  companyWebsite,
  apiKey,
  onSuccess,
  onError,
}) => {
  const [activeTab, setActiveTab] = useState<"upload" | "settings" | "preview">("settings");
  const [selectedFiles, setSelectedFiles] = useState<File[]>([]);
  const [settings, setSettings] = useState<FinetuneSettings>({
    language: "english",
    tone: "formal",
    max_tokens: 1000,
  });
  const [promptPreview, setPromptPreview] = useState<string>("");
  const [previewMetadata, setPreviewMetadata] = useState<any>(null);
  const [isLoading, setIsLoading] = useState(false);
  const [uploadProgress, setUploadProgress] = useState(0);
  const [errors, setErrors] = useState<string[]>([]);

  // Fetch current settings on mount
  useEffect(() => {
    const fetchSettings = async () => {
      try {
        const response = await fetch(
          `/api/v1/company/${companyId}/settings`,
          {
            headers: {
              "X-API-Key": apiKey,
            },
          }
        );
        if (response.ok) {
          const data = await response.json();
          setSettings({
            language: data.language,
            tone: data.tone,
            max_tokens: data.max_tokens,
          });
          // Generate initial preview
          await generatePreview(data);
        }
      } catch (error) {
        console.error("Failed to fetch settings:", error);
      }
    };

    fetchSettings();
  }, [companyId, apiKey]);

  const generatePreview = useCallback(
    async (currentSettings?: FinetuneSettings) => {
      const settingsToUse = currentSettings || settings;
      try {
        const response = await fetch(
          `/api/v1/company/${companyId}/prompt/preview`,
          {
            method: "POST",
            headers: {
              "Content-Type": "application/json",
              "X-API-Key": apiKey,
            },
            body: JSON.stringify({
              tone: settingsToUse.tone,
              language: settingsToUse.language,
              max_tokens: settingsToUse.max_tokens,
              company_name: companyName,
              category: companyCategory,
              website: companyWebsite,
            }),
          }
        );

        if (response.ok) {
          const data = await response.json();
          setPromptPreview(data.prompt);
          setPreviewMetadata(data.metadata);
        }
      } catch (error) {
        console.error("Failed to generate preview:", error);
      }
    },
    [companyId, apiKey, companyName, companyCategory, companyWebsite]
  );

  const handleSettingsSave = useCallback(
    async (newSettings: FinetuneSettings) => {
      setIsLoading(true);
      setErrors([]);
      try {
        const response = await fetch(
          `/api/v1/company/${companyId}/settings`,
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
        await generatePreview(newSettings);
        onSuccess?.(
          `Settings saved successfully! ${newSettings.tone} tone, ${newSettings.language} language.`
        );
      } catch (error) {
        const message = error instanceof Error ? error.message : "Failed to save settings";
        setErrors([message]);
        onError?.(message);
      } finally {
        setIsLoading(false);
      }
    },
    [companyId, apiKey, generatePreview, onSuccess, onError]
  );

  const handleFilesSelected = useCallback((files: File[]) => {
    setSelectedFiles((prev) => [...prev, ...files]);
  }, []);

  const handleUploadFiles = useCallback(async () => {
    if (selectedFiles.length === 0) {
      setErrors(["No files selected"]);
      return;
    }

    setIsLoading(true);
    setErrors([]);

    try {
      const formData = new FormData();
      selectedFiles.forEach((file) => {
        formData.append("files", file);
      });

      const xhr = new XMLHttpRequest();

      // Track upload progress
      xhr.upload.addEventListener("progress", (e) => {
        if (e.lengthComputable) {
          const progress = (e.loaded / e.total) * 100;
          setUploadProgress(progress);
        }
      });

      xhr.addEventListener("load", async () => {
        if (xhr.status === 200 || xhr.status === 201) {
          setSelectedFiles([]);
          setUploadProgress(0);
          await generatePreview();
          onSuccess?.(
            `Successfully uploaded ${selectedFiles.length} file(s) to knowledge base`
          );
        } else {
          throw new Error("Upload failed");
        }
      });

      xhr.addEventListener("error", () => {
        throw new Error("Upload failed");
      });

      xhr.open(
        "POST",
        `/api/v1/company/${companyId}/finetune/upload`
      );
      xhr.setRequestHeader("X-API-Key", apiKey);
      xhr.send(formData);
    } catch (error) {
      const message = error instanceof Error ? error.message : "Failed to upload files";
      setErrors([message]);
      onError?.(message);
    } finally {
      setIsLoading(false);
    }
  }, [selectedFiles, companyId, apiKey, generatePreview, onSuccess, onError]);

  return (
    <div className="w-full space-y-6">
      {/* Header */}
      <div className="flex items-center gap-3">
        <div className="p-3 bg-gradient-to-br from-blue-500 to-purple-600 rounded-lg">
          <Zap className="h-6 w-6 text-white" />
        </div>
        <div>
          <h1 className="text-2xl font-bold text-gray-900 dark:text-gray-100">
            Finetune Playground
          </h1>
          <p className="text-sm text-gray-600 dark:text-gray-400 mt-0.5">
            Customize your AI assistant's behavior and knowledge
          </p>
        </div>
      </div>

      {/* Error Messages */}
      {errors.length > 0 && (
        <div className="bg-red-50 dark:bg-red-950 border border-red-200 dark:border-red-800 rounded-lg p-4 flex gap-3">
          <AlertCircle className="h-5 w-5 text-red-600 dark:text-red-400 flex-shrink-0 mt-0.5" />
          <div className="flex-1">
            {errors.map((error, idx) => (
              <p key={idx} className="text-sm text-red-700 dark:text-red-200">
                {error}
              </p>
            ))}
          </div>
        </div>
      )}

      {/* Tabs */}
      <div className="flex gap-2 border-b border-gray-200 dark:border-gray-700">
        {(["settings", "upload", "preview"] as const).map((tab) => (
          <button
            key={tab}
            onClick={() => setActiveTab(tab)}
            className={cn(
              "px-4 py-3 font-medium border-b-2 transition-colors capitalize",
              activeTab === tab
                ? "border-blue-600 text-blue-600 dark:text-blue-400"
                : "border-transparent text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200"
            )}
          >
            {tab}
          </button>
        ))}
      </div>

      {/* Tab Content */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Main Content */}
        <div className="lg:col-span-2">
          {activeTab === "settings" && (
            <SettingsForm
              initialSettings={settings}
              onSettingsSave={handleSettingsSave}
              isLoading={isLoading}
            />
          )}

          {activeTab === "upload" && (
            <div className="space-y-4">
              <div>
                <h3 className="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">
                  Upload Knowledge Base Files
                </h3>
                <p className="text-sm text-gray-600 dark:text-gray-400">
                  Upload documents (.txt, .md, .pdf) to train your AI assistant with company-specific knowledge
                </p>
              </div>

              <DragAndDrop
                onFilesSelected={handleFilesSelected}
                acceptedFileTypes={[".txt", ".md", ".pdf"]}
                maxFileSize={10 * 1024 * 1024}
                maxFiles={5}
                isLoading={isLoading}
              />

              {selectedFiles.length > 0 && (
                <div className="space-y-3">
                  {uploadProgress > 0 && uploadProgress < 100 && (
                    <div className="space-y-2">
                      <div className="flex items-center justify-between text-sm">
                        <span className="text-gray-700 dark:text-gray-300">Uploading...</span>
                        <span className="font-medium text-blue-600 dark:text-blue-400">
                          {Math.round(uploadProgress)}%
                        </span>
                      </div>
                      <div className="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 overflow-hidden">
                        <div
                          className="bg-blue-600 dark:bg-blue-400 h-full transition-all duration-300"
                          style={{ width: `${uploadProgress}%` }}
                        ></div>
                      </div>
                    </div>
                  )}

                  <button
                    onClick={handleUploadFiles}
                    disabled={isLoading}
                    className={cn(
                      "w-full px-6 py-3 rounded-lg font-medium transition-colors",
                      !isLoading
                        ? "bg-blue-600 text-white hover:bg-blue-700"
                        : "bg-gray-200 dark:bg-gray-700 text-gray-500 dark:text-gray-400 cursor-not-allowed"
                    )}
                  >
                    {isLoading ? "Uploading..." : "Upload Files"}
                  </button>
                </div>
              )}
            </div>
          )}

          {activeTab === "preview" && (
            <PromptPreview
              prompt={promptPreview}
              metadata={previewMetadata}
              isLoading={isLoading}
            />
          )}
        </div>

        {/* Preview Sidebar */}
        <div className="lg:col-span-1">
          <div className="sticky top-6">
            <PromptPreview
              prompt={promptPreview}
              metadata={previewMetadata}
              isLoading={isLoading}
            />
          </div>
        </div>
      </div>
    </div>
  );
};

export default FinetunePanel;
