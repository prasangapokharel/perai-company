# File Management Endpoints

Upload and manage company files including logos and content. Files are stored securely on the server.

---

## File Management Overview

```
File Storage Structure:
backend/storage/companies/{company_id}/
├── logo/
│   └── company_logo.png
└── content/
    ├── brochure.pdf
    ├── whitepaper.pdf
    └── product_guide.pdf

Features:
- Automatic filename sanitization
- MIME type validation
- File size tracking
- Automatic cleanup on company delete
```

---

## File Storage Rules

### Allowed File Types

| Category | Types | MIME Types |
|----------|-------|-----------|
| **Images (Logo)** | PNG, JPEG, GIF, WebP | `image/png`, `image/jpeg`, `image/gif`, `image/webp` |
| **Documents** | PDF, DOCX, TXT | `application/pdf`, `application/vnd.openxmlformats-officedocument.wordprocessingml.document`, `text/plain` |
| **Archives** | ZIP | `application/zip` |

### File Size Limits
- Logo: up to 10 MB
- Content: up to 50 MB per file
- Total per company: 1 GB

---

## 1. Upload Logo

Upload company logo image.

### Endpoint
```
POST /api/v1/company/{company_id}/files/logo
```

### Headers
```
X-API-Key: sk_YOUR_API_KEY
Content-Type: multipart/form-data
```

### Path Parameters
| Parameter | Type | Required |
|-----------|------|----------|
| `company_id` | integer | Yes |

### Form Data
| Field | Type | Required | Notes |
|-------|------|----------|-------|
| `file` | file | Yes | Image file (PNG, JPEG, GIF, WebP) |

### Response (200 OK)
```json
{
  "filename": "company_logo.png",
  "file_size": 24576,
  "mime_type": "image/png",
  "storage_path": "/storage/companies/1/logo/company_logo.png",
  "url": "http://localhost:8000/api/v1/company/1/files/logo/company_logo.png",
  "uploaded_at": "2026-05-29T14:00:00Z"
}
```

### Response Fields
| Field | Type | Notes |
|-------|------|-------|
| `filename` | string | Sanitized original filename |
| `file_size` | integer | Size in bytes |
| `mime_type` | string | File MIME type |
| `storage_path` | string | Server-side storage location |
| `url` | string | Public URL to access file |

### Example cURL
```bash
curl -X POST http://localhost:8000/api/v1/company/1/files/logo \
  -H "X-API-Key: sk_YOUR_API_KEY" \
  -F "file=@/path/to/logo.png"
```

### Example JavaScript (Fetch)
```javascript
async function uploadLogo(companyId, file) {
  const formData = new FormData();
  formData.append('file', file);

  const response = await fetch(`http://localhost:8000/api/v1/company/${companyId}/files/logo`, {
    method: 'POST',
    headers: {
      'X-API-Key': localStorage.getItem('perai_api_key')
    },
    body: formData
  });

  const result = await response.json();
  return result.url; // Use this to display logo
}

// Usage with file input
document.getElementById('logoInput').addEventListener('change', async (e) => {
  const file = e.target.files[0];
  const logoUrl = await uploadLogo(1, file);
  document.getElementById('logoPreview').src = logoUrl;
});
```

### Example React Component
```jsx
import { useState } from 'react';

export function LogoUpload() {
  const [preview, setPreview] = useState(null);
  const [uploading, setUploading] = useState(false);

  const handleLogoChange = async (e) => {
    const file = e.target.files[0];
    if (!file) return;

    setUploading(true);
    const formData = new FormData();
    formData.append('file', file);

    try {
      const response = await fetch(`http://localhost:8000/api/v1/company/1/files/logo`, {
        method: 'POST',
        headers: {
          'X-API-Key': localStorage.getItem('perai_api_key')
        },
        body: formData
      });

      const result = await response.json();
      setPreview(result.url);
    } catch (error) {
      console.error('Upload failed:', error);
    } finally {
      setUploading(false);
    }
  };

  return (
    <div className="logo-upload">
      <input
        type="file"
        accept="image/*"
        onChange={handleLogoChange}
        disabled={uploading}
      />
      {preview && <img src={preview} alt="Logo" className="preview" />}
    </div>
  );
}
```

### Errors
| Status | Error | Cause |
|--------|-------|-------|
| `400` | `"No file provided"` | File field missing from request |
| `400` | `"Invalid file type"` | File is not PNG, JPEG, GIF, or WebP |
| `400` | `"File too large"` | File exceeds 10 MB limit |
| `401` | `"Invalid API key"` | Missing or invalid API key |
| `404` | `"Company not found"` | Invalid company_id |

---

## 2. Get Logo

Retrieve company logo.

### Endpoint
```
GET /api/v1/company/{company_id}/files/logo/{filename}
```

### Path Parameters
| Parameter | Type | Required |
|-----------|------|----------|
| `company_id` | integer | Yes |
| `filename` | string | Yes |

### Response (200 OK)
```
(binary image file)
```

### Example HTML
```html
<img src="http://localhost:8000/api/v1/company/1/files/logo/company_logo.png" />
```

### Example JavaScript
```javascript
const logoUrl = `http://localhost:8000/api/v1/company/1/files/logo/company_logo.png`;
const img = document.createElement('img');
img.src = logoUrl;
document.body.appendChild(img);
```

---

## 3. Upload Content File

Upload company document or content file.

### Endpoint
```
POST /api/v1/company/{company_id}/files/content
```

### Headers
```
X-API-Key: sk_YOUR_API_KEY
Content-Type: multipart/form-data
```

### Path Parameters
| Parameter | Type | Required |
|-----------|------|----------|
| `company_id` | integer | Yes |

### Form Data
| Field | Type | Required | Notes |
|-------|------|----------|-------|
| `file` | file | Yes | Document (PDF, DOCX, TXT, ZIP) |

### Response (200 OK)
```json
{
  "filename": "company_brochure.pdf",
  "file_size": 1048576,
  "mime_type": "application/pdf",
  "storage_path": "/storage/companies/1/content/company_brochure.pdf",
  "url": "http://localhost:8000/api/v1/company/1/files/content/company_brochure.pdf",
  "uploaded_at": "2026-05-29T14:30:00Z"
}
```

### Example cURL
```bash
curl -X POST http://localhost:8000/api/v1/company/1/files/content \
  -H "X-API-Key: sk_YOUR_API_KEY" \
  -F "file=@/path/to/brochure.pdf"
```

### Example JavaScript
```javascript
async function uploadContent(companyId, file) {
  const formData = new FormData();
  formData.append('file', file);

  const response = await fetch(`http://localhost:8000/api/v1/company/${companyId}/files/content`, {
    method: 'POST',
    headers: {
      'X-API-Key': localStorage.getItem('perai_api_key')
    },
    body: formData
  });

  return await response.json();
}

// Usage
const fileInput = document.getElementById('contentInput');
fileInput.addEventListener('change', async (e) => {
  const file = e.target.files[0];
  const result = await uploadContent(1, file);
  console.log('Uploaded:', result.filename);
  console.log('Download:', result.url);
});
```

### Errors
| Status | Error | Cause |
|--------|-------|-------|
| `400` | `"No file provided"` | File field missing |
| `400` | `"Invalid file type"` | File type not allowed |
| `400` | `"File too large"` | File exceeds 50 MB limit |
| `401` | `"Invalid API key"` | Missing or invalid API key |
| `404` | `"Company not found"` | Invalid company_id |

---

## 4. Get Content File

Download company content file.

### Endpoint
```
GET /api/v1/company/{company_id}/files/content/{filename}
```

### Path Parameters
| Parameter | Type | Required |
|-----------|------|----------|
| `company_id` | integer | Yes |
| `filename` | string | Yes |

### Response (200 OK)
```
(binary file)
```

### Example HTML
```html
<a href="http://localhost:8000/api/v1/company/1/files/content/brochure.pdf" download>
  Download Brochure
</a>
```

### Example JavaScript
```javascript
function downloadFile(companyId, filename) {
  const url = `http://localhost:8000/api/v1/company/${companyId}/files/content/${filename}`;
  const link = document.createElement('a');
  link.href = url;
  link.download = filename;
  link.click();
}

// Usage
downloadFile(1, 'brochure.pdf');
```

---

## 5. List Company Files

Retrieve all files for a company.

### Endpoint
```
GET /api/v1/company/{company_id}/files
```

### Headers
```
X-API-Key: sk_YOUR_API_KEY
Content-Type: application/json
```

### Path Parameters
| Parameter | Type | Required |
|-----------|------|----------|
| `company_id` | integer | Yes |

### Response (200 OK)
```json
{
  "company_id": 1,
  "total_size_bytes": 2097152,
  "logo": {
    "filename": "company_logo.png",
    "file_size": 24576,
    "mime_type": "image/png",
    "uploaded_at": "2026-05-29T14:00:00Z",
    "url": "http://localhost:8000/api/v1/company/1/files/logo/company_logo.png"
  },
  "content": [
    {
      "filename": "brochure.pdf",
      "file_size": 1048576,
      "mime_type": "application/pdf",
      "uploaded_at": "2026-05-29T14:30:00Z",
      "url": "http://localhost:8000/api/v1/company/1/files/content/brochure.pdf"
    },
    {
      "filename": "whitepaper.pdf",
      "file_size": 1024000,
      "mime_type": "application/pdf",
      "uploaded_at": "2026-05-29T15:00:00Z",
      "url": "http://localhost:8000/api/v1/company/1/files/content/whitepaper.pdf"
    }
  ]
}
```

### Example cURL
```bash
curl -X GET http://localhost:8000/api/v1/company/1/files \
  -H "X-API-Key: sk_YOUR_API_KEY"
```

### Example JavaScript
```javascript
async function listFiles(companyId) {
  const response = await fetch(`http://localhost:8000/api/v1/company/${companyId}/files`, {
    headers: {
      'X-API-Key': localStorage.getItem('perai_api_key')
    }
  });

  const result = await response.json();
  
  console.log('Total storage used:', (result.total_size_bytes / 1024 / 1024).toFixed(2) + ' MB');
  console.log('Logo:', result.logo?.filename || 'None');
  console.log('Content files:', result.content.length);
  
  result.content.forEach(file => {
    console.log(`- ${file.filename} (${(file.file_size / 1024).toFixed(2)} KB)`);
  });
}
```

### Example React Component
```jsx
import { useState, useEffect } from 'react';

export function FileManager() {
  const [files, setFiles] = useState(null);

  useEffect(() => {
    async function loadFiles() {
      const response = await fetch(`http://localhost:8000/api/v1/company/1/files`, {
        headers: {
          'X-API-Key': localStorage.getItem('perai_api_key')
        }
      });
      const data = await response.json();
      setFiles(data);
    }
    loadFiles();
  }, []);

  if (!files) return <div>Loading...</div>;

  return (
    <div className="file-manager">
      <h2>Files</h2>
      
      {files.logo && (
        <div className="logo-section">
          <img src={files.logo.url} alt="Logo" />
          <p>{files.logo.filename}</p>
        </div>
      )}

      <div className="content-section">
        <h3>Documents</h3>
        <ul>
          {files.content.map(file => (
            <li key={file.filename}>
              <a href={file.url} download>{file.filename}</a>
              <span>{(file.file_size / 1024).toFixed(2)} KB</span>
            </li>
          ))}
        </ul>
      </div>

      <p>Total: {(files.total_size_bytes / 1024 / 1024).toFixed(2)} MB</p>
    </div>
  );
}
```

### Errors
| Status | Error | Cause |
|--------|-------|-------|
| `401` | `"Invalid API key"` | Missing or invalid API key |
| `404` | `"Company not found"` | Invalid company_id |

---

## 6. Delete File

Delete a specific company file.

### Endpoint
```
DELETE /api/v1/company/{company_id}/files/{file_type}/{filename}
```

### Headers
```
X-API-Key: sk_YOUR_API_KEY
Content-Type: application/json
```

### Path Parameters
| Parameter | Type | Required | Notes |
|-----------|------|----------|-------|
| `company_id` | integer | Yes | Company ID |
| `file_type` | string | Yes | `logo` or `content` |
| `filename` | string | Yes | File to delete |

### Response (204 No Content)
```
(empty)
```

### Example cURL
```bash
# Delete logo
curl -X DELETE http://localhost:8000/api/v1/company/1/files/logo/company_logo.png \
  -H "X-API-Key: sk_YOUR_API_KEY"

# Delete content file
curl -X DELETE http://localhost:8000/api/v1/company/1/files/content/brochure.pdf \
  -H "X-API-Key: sk_YOUR_API_KEY"
```

### Example JavaScript
```javascript
async function deleteFile(companyId, fileType, filename) {
  const response = await fetch(
    `http://localhost:8000/api/v1/company/${companyId}/files/${fileType}/${filename}`,
    {
      method: 'DELETE',
      headers: {
        'X-API-Key': localStorage.getItem('perai_api_key')
      }
    }
  );

  if (response.status === 204) {
    console.log('File deleted successfully');
  }
}

// Usage
deleteFile(1, 'content', 'brochure.pdf');
```

### Errors
| Status | Error | Cause |
|--------|-------|-------|
| `401` | `"Invalid API key"` | Missing or invalid API key |
| `404` | `"Company not found"` | Invalid company_id |
| `404` | `"File not found"` | Filename doesn't exist |

---

## File Management Workflow

```
┌─────────────────────────────────┐
│ Company Registration            │
│ company_id = 1                  │
└────────────┬────────────────────┘
             │
             ▼
┌─────────────────────────────────┐
│ 1. Upload Logo                  │
│ POST /files/logo                │
│ → Stored at /storage/.../1/logo/│
└────────────┬────────────────────┘
             │
             ▼
┌─────────────────────────────────┐
│ 2. Upload Content Files         │
│ POST /files/content             │
│ Multiple files allowed          │
│ → Stored at /storage/.../1/     │
│           content/              │
└────────────┬────────────────────┘
             │
             ▼
┌─────────────────────────────────┐
│ 3. List All Files               │
│ GET /files                      │
│ View storage usage              │
│ Get download URLs               │
└────────────┬────────────────────┘
             │
             ▼
┌─────────────────────────────────┐
│ 4. Delete Files (Optional)      │
│ DELETE /files/{type}/{name}     │
│ Free up storage                 │
└────────────┬────────────────────┘
             │
             ▼
┌─────────────────────────────────┐
│ 5. Auto-Cleanup on             │
│ Company Delete                  │
│ DELETE /company/{id}            │
│ All files auto-deleted          │
└─────────────────────────────────┘
```

---

## File Best Practices

### 1. Validation
```javascript
// Check file type and size before upload
function validateFile(file, maxSize = 10 * 1024 * 1024) {
  const validTypes = ['image/png', 'image/jpeg', 'image/gif', 'image/webp'];
  
  if (!validTypes.includes(file.type)) {
    throw new Error('Invalid file type. Use PNG, JPEG, GIF, or WebP');
  }
  
  if (file.size > maxSize) {
    throw new Error(`File too large. Maximum ${maxSize / 1024 / 1024}MB`);
  }
  
  return true;
}
```

### 2. Progress Tracking
```javascript
async function uploadWithProgress(file) {
  const xhr = new XMLHttpRequest();
  
  xhr.upload.addEventListener('progress', (e) => {
    const percentComplete = (e.loaded / e.total) * 100;
    console.log(`Upload progress: ${percentComplete.toFixed(2)}%`);
  });

  // Use XMLHttpRequest or fetch with readable stream
}
```

### 3. Error Recovery
```javascript
async function uploadWithRetry(companyId, file, maxRetries = 3) {
  for (let i = 0; i < maxRetries; i++) {
    try {
      const result = await uploadContent(companyId, file);
      return result;
    } catch (error) {
      if (i === maxRetries - 1) throw error;
      console.log(`Retry ${i + 1}/${maxRetries}`);
      await new Promise(r => setTimeout(r, 1000 * (i + 1))); // Exponential backoff
    }
  }
}
```

---

## Storage Limits

| Limit | Value |
|-------|-------|
| Logo file size | 10 MB |
| Content file size | 50 MB |
| Total per company | 1 GB |

Upgrading limits available in Phase 2 enterprise plans.

---

## Security

- Files stored on encrypted disk
- MIME type validation enforced
- Filenames sanitized to prevent path traversal
- Access requires valid API key
- Files auto-deleted when company deleted
- No direct filesystem access from API

---

## Next Steps

1. **Upload Logo** → Use endpoint 1
2. **Upload Content** → Use endpoint 3
3. **List Files** → Use endpoint 5
4. **Integrate in UI** → Show logo, provide download links
5. **Reference in Chat** → Use file URLs in knowledge base

---

## Future Features (Phase 2)

- Bulk upload
- Drag-and-drop interface
- File versioning
- Storage analytics
- Custom storage backends (S3, GCS)
- File preview in browser
- CDN distribution
