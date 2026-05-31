"use client";

import React, { useState, useCallback } from "react";
import { Upload, X, File, AlertCircle } from "lucide-react";
import { cn } from "@/lib/utils";

export interface DragAndDropProps {
  onFilesSelected: (files: File[]) => void;
  acceptedFileTypes?: string[];
  maxFileSize?: number; // in bytes
  maxFiles?: number;
  disabled?: boolean;
  isLoading?: boolean;
}

export const DragAndDrop: React.FC<DragAndDropProps> = ({
  onFilesSelected,
  acceptedFileTypes = [".txt", ".md", ".pdf"],
  maxFileSize = 10 * 1024 * 1024, // 10MB default
  maxFiles = 5,
  disabled = false,
  isLoading = false,
}) => {
  const [isDragActive, setIsDragActive] = useState(false);
  const [selectedFiles, setSelectedFiles] = useState<File[]>([]);
  const [errors, setErrors] = useState<string[]>([]);

  const validateFiles = useCallback(
    (files: FileList | File[]): { valid: File[]; errors: string[] } => {
      const validFiles: File[] = [];
      const fileErrors: string[] = [];

      Array.from(files).forEach((file, index) => {
        // Check file type
        const fileExt = "." + file.name.split(".").pop()?.toLowerCase();
        if (!acceptedFileTypes.includes(fileExt)) {
          fileErrors.push(
            `File ${index + 1} (${file.name}): Invalid file type. Accepted: ${acceptedFileTypes.join(", ")}`
          );
          return;
        }

        // Check file size
        if (file.size > maxFileSize) {
          fileErrors.push(
            `File ${index + 1} (${file.name}): File too large. Max ${(maxFileSize / 1024 / 1024).toFixed(2)}MB`
          );
          return;
        }

        validFiles.push(file);
      });

      // Check total files count
      if (validFiles.length + selectedFiles.length > maxFiles) {
        fileErrors.push(
          `Too many files. Maximum ${maxFiles} files allowed. Current: ${selectedFiles.length}, Adding: ${validFiles.length}`
        );
      }

      return { valid: validFiles, errors: fileErrors };
    },
    [selectedFiles, acceptedFileTypes, maxFileSize, maxFiles]
  );

  const handleDrag = useCallback((e: React.DragEvent) => {
    e.preventDefault();
    e.stopPropagation();
    if (!disabled && !isLoading) {
      setIsDragActive(e.type === "dragenter" || e.type === "dragover");
    }
  }, [disabled, isLoading]);

  const handleDrop = useCallback(
    (e: React.DragEvent) => {
      e.preventDefault();
      e.stopPropagation();
      setIsDragActive(false);

      if (disabled || isLoading) return;

      const { valid, errors } = validateFiles(e.dataTransfer.files);
      if (valid.length > 0) {
        const newFiles = [...selectedFiles, ...valid];
        setSelectedFiles(newFiles);
        onFilesSelected(valid);
      }
      if (errors.length > 0) {
        setErrors(errors);
      }
    },
    [disabled, isLoading, selectedFiles, validateFiles, onFilesSelected]
  );

  const handleChange = useCallback(
    (e: React.ChangeEvent<HTMLInputElement>) => {
      if (e.target.files) {
        const { valid, errors } = validateFiles(e.target.files);
        if (valid.length > 0) {
          const newFiles = [...selectedFiles, ...valid];
          setSelectedFiles(newFiles);
          onFilesSelected(valid);
        }
        if (errors.length > 0) {
          setErrors(errors);
        }
      }
    },
    [selectedFiles, validateFiles, onFilesSelected]
  );

  const removeFile = useCallback((index: number) => {
    setSelectedFiles((prev) => {
      const newFiles = prev.filter((_, i) => i !== index);
      return newFiles;
    });
  }, []);

  const clearErrors = useCallback(() => {
    setErrors([]);
  }, []);

  const formatFileSize = (bytes: number): string => {
    if (bytes === 0) return "0 Bytes";
    const k = 1024;
    const sizes = ["Bytes", "KB", "MB"];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + " " + sizes[i];
  };

  return (
    <div className="w-full space-y-4">
      {/* Drag and Drop Area */}
      <div
        onDragEnter={handleDrag}
        onDragLeave={handleDrag}
        onDragOver={handleDrag}
        onDrop={handleDrop}
        className={cn(
          "relative rounded-lg border-2 border-dashed transition-colors duration-200",
          isDragActive
            ? "border-blue-500 bg-blue-50 dark:bg-blue-950"
            : "border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-900",
          disabled || isLoading
            ? "opacity-50 cursor-not-allowed"
            : "cursor-pointer hover:border-gray-400 dark:hover:border-gray-500"
        )}
      >
        <input
          type="file"
          multiple
          onChange={handleChange}
          disabled={disabled || isLoading}
          accept={acceptedFileTypes.join(",")}
          className="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
        />

        <div className="flex flex-col items-center justify-center py-12 px-4">
          <Upload className="h-12 w-12 text-gray-400 mb-3" />
          <div className="text-center">
            <p className="text-lg font-medium text-gray-700 dark:text-gray-300">
              Drag and drop your files here
            </p>
            <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">
              or click to browse
            </p>
          </div>
          <div className="mt-4 text-xs text-gray-500 dark:text-gray-400">
            <p>
              Supported: {acceptedFileTypes.join(", ")} • Max{" "}
              {(maxFileSize / 1024 / 1024).toFixed(2)}MB per file
            </p>
            <p>Maximum {maxFiles} files</p>
          </div>
          {isLoading && (
            <div className="mt-4">
              <div className="animate-spin inline-block h-5 w-5 border-2 border-blue-500 border-r-transparent rounded-full"></div>
            </div>
          )}
        </div>
      </div>

      {/* Error Messages */}
      {errors.length > 0 && (
        <div className="bg-red-50 dark:bg-red-950 border border-red-200 dark:border-red-800 rounded-lg p-4">
          <div className="flex items-start gap-3">
            <AlertCircle className="h-5 w-5 text-red-500 flex-shrink-0 mt-0.5" />
            <div className="flex-1">
              <h3 className="font-medium text-red-900 dark:text-red-200">
                Upload Errors
              </h3>
              <ul className="mt-2 space-y-1">
                {errors.map((error, idx) => (
                  <li key={idx} className="text-sm text-red-700 dark:text-red-300">
                    {error}
                  </li>
                ))}
              </ul>
            </div>
            <button
              onClick={clearErrors}
              className="text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-200 flex-shrink-0"
            >
              <X className="h-4 w-4" />
            </button>
          </div>
        </div>
      )}

      {/* Selected Files List */}
      {selectedFiles.length > 0 && (
        <div className="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
          <h3 className="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3">
            Selected Files ({selectedFiles.length}/{maxFiles})
          </h3>
          <div className="space-y-2">
            {selectedFiles.map((file, idx) => (
              <div
                key={idx}
                className="flex items-center justify-between bg-gray-50 dark:bg-gray-900 p-3 rounded-lg"
              >
                <div className="flex items-center gap-3 flex-1 min-w-0">
                  <File className="h-5 w-5 text-blue-500 flex-shrink-0" />
                  <div className="flex-1 min-w-0">
                    <p className="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">
                      {file.name}
                    </p>
                    <p className="text-xs text-gray-500 dark:text-gray-400">
                      {formatFileSize(file.size)}
                    </p>
                  </div>
                </div>
                <button
                  onClick={() => removeFile(idx)}
                  disabled={isLoading}
                  className="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 flex-shrink-0 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  <X className="h-4 w-4" />
                </button>
              </div>
            ))}
          </div>
        </div>
      )}
    </div>
  );
};

export default DragAndDrop;
