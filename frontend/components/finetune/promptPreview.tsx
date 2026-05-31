"use client";

import React from "react";
import { Copy, Eye } from "lucide-react";
import { cn } from "@/lib/utils";

export interface PromptPreviewProps {
  prompt: string;
  metadata?: {
    company_name?: string;
    tone?: string;
    language?: string;
    max_tokens?: number;
    prompt_length?: number;
    prompt_char_count?: number;
  };
  isLoading?: boolean;
}

export const PromptPreview: React.FC<PromptPreviewProps> = ({
  prompt,
  metadata,
  isLoading = false,
}) => {
  const [copied, setCopied] = React.useState(false);

  const handleCopyPrompt = React.useCallback(() => {
    navigator.clipboard.writeText(prompt).then(() => {
      setCopied(true);
      setTimeout(() => setCopied(false), 2000);
    });
  }, [prompt]);

  return (
    <div className="w-full space-y-4">
      {/* Header */}
      <div className="flex items-center gap-2">
        <Eye className="h-5 w-5 text-gray-700 dark:text-gray-300" />
        <h3 className="text-lg font-semibold text-gray-900 dark:text-gray-100">
          Prompt Preview
        </h3>
      </div>

      {/* Metadata */}
      {metadata && (
        <div className="grid grid-cols-2 md:grid-cols-4 gap-4 bg-gray-50 dark:bg-gray-900 p-4 rounded-lg">
          {metadata.company_name && (
            <div>
              <p className="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                Company
              </p>
              <p className="text-sm text-gray-900 dark:text-gray-100 font-medium">
                {metadata.company_name}
              </p>
            </div>
          )}
          {metadata.tone && (
            <div>
              <p className="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                Tone
              </p>
              <p className="text-sm text-gray-900 dark:text-gray-100 font-medium capitalize">
                {metadata.tone}
              </p>
            </div>
          )}
          {metadata.language && (
            <div>
              <p className="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                Language
              </p>
              <p className="text-sm text-gray-900 dark:text-gray-100 font-medium capitalize">
                {metadata.language}
              </p>
            </div>
          )}
          {metadata.max_tokens && (
            <div>
              <p className="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                Max Tokens
              </p>
              <p className="text-sm text-gray-900 dark:text-gray-100 font-medium">
                {metadata.max_tokens}
              </p>
            </div>
          )}
        </div>
      )}

      {/* Prompt Content */}
      {isLoading ? (
        <div className="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
          <div className="space-y-4">
            <div className="h-4 bg-gray-200 dark:bg-gray-700 rounded animate-pulse"></div>
            <div className="h-4 bg-gray-200 dark:bg-gray-700 rounded animate-pulse"></div>
            <div className="h-4 bg-gray-200 dark:bg-gray-700 rounded animate-pulse w-3/4"></div>
          </div>
        </div>
      ) : (
        <div className="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
          {/* Copy Button */}
          <div className="flex items-center justify-between bg-gray-50 dark:bg-gray-900 px-4 py-3 border-b border-gray-200 dark:border-gray-700">
            <p className="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
              System Prompt
            </p>
            <button
              onClick={handleCopyPrompt}
              className={cn(
                "flex items-center gap-2 px-3 py-1.5 rounded text-sm font-medium transition-colors",
                copied
                  ? "bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-200"
                  : "bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-200 hover:bg-blue-200 dark:hover:bg-blue-800"
              )}
            >
              <Copy className="h-3.5 w-3.5" />
              {copied ? "Copied!" : "Copy"}
            </button>
          </div>

          {/* Prompt Text */}
          <div className="p-6 max-h-96 overflow-y-auto">
            <pre className="whitespace-pre-wrap break-words text-sm text-gray-700 dark:text-gray-300 font-mono leading-relaxed">
              {prompt}
            </pre>
          </div>

          {/* Stats */}
          {metadata && (
            <div className="bg-gray-50 dark:bg-gray-900 px-4 py-3 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between text-xs text-gray-600 dark:text-gray-400">
              <span>{metadata.prompt_length || 0} words</span>
              <span>•</span>
              <span>{metadata.prompt_char_count || 0} characters</span>
            </div>
          )}
        </div>
      )}
    </div>
  );
};

export default PromptPreview;
