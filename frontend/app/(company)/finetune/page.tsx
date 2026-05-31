"use client";

import React, { useEffect, useState } from "react";
import { useRouter } from "next/navigation";
import { FinetunePanel } from "@/components/finetune/finetunePanel";
import { ArrowLeft, AlertCircle } from "lucide-react";
import Link from "next/link";

export default function FinetunePlaygroundPage() {
  const router = useRouter();
  const [companyData, setCompanyData] = useState<any>(null);
  const [apiKey, setApiKey] = useState<string>("");
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string>("");
  const [successMessage, setSuccessMessage] = useState<string>("");

  useEffect(() => {
    // Fetch company data from session/localStorage
    const storedCompanyId = localStorage.getItem("companyId");
    const storedApiKey = localStorage.getItem("apiKey");
    const storedCompanyData = localStorage.getItem("companyData");

    if (!storedCompanyId || !storedApiKey) {
      setError("No company data found. Please log in first.");
      setIsLoading(false);
      return;
    }

    try {
      const parsedData = storedCompanyData ? JSON.parse(storedCompanyData) : {};
      setCompanyData({
        id: storedCompanyId,
        name: parsedData.name || "Your Company",
        category: parsedData.category || "Service Provider",
        website: parsedData.website || "https://example.com",
      });
      setApiKey(storedApiKey);
    } catch (err) {
      setError("Failed to load company data");
    } finally {
      setIsLoading(false);
    }
  }, []);

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
                Error Loading Finetune Playground
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
              {companyData.name} - Finetune Playground
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

        {/* Finetune Panel */}
        <FinetunePanel
          companyId={companyData.id}
          companyName={companyData.name}
          companyCategory={companyData.category}
          companyWebsite={companyData.website}
          apiKey={apiKey}
          onSuccess={(message) => {
            setSuccessMessage(message);
            setTimeout(() => setSuccessMessage(""), 5000);
          }}
          onError={(error) => {
            setError(error);
            setTimeout(() => setError(""), 5000);
          }}
        />
      </div>
    </div>
  );
}
