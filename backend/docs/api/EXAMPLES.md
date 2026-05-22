# Complete Integration Examples

Complete, copy-paste ready examples for common frontend scenarios.

## Table of Contents
1. [React Examples](#react-examples)
2. [Vue Examples](#vue-examples)
3. [TypeScript/Node Examples](#typescriptnodejs-examples)
4. [cURL Examples](#curl-examples)

---

## React Examples

### 1. Simple Company List

```jsx
import { useState, useEffect } from 'react';

function CompanyList() {
  const [companies, setCompanies] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  const API_KEY = process.env.REACT_APP_API_KEY;

  useEffect(() => {
    const fetchCompanies = async () => {
      try {
        const response = await fetch('http://localhost:8000/api/v1/company', {
          headers: { 'X-API-Key': API_KEY },
        });

        if (!response.ok) {
          throw new Error(`HTTP ${response.status}`);
        }

        const data = await response.json();
        setCompanies(data);
      } catch (err) {
        setError(err.message);
      } finally {
        setLoading(false);
      }
    };

    fetchCompanies();
  }, []);

  if (loading) return <div>Loading...</div>;
  if (error) return <div>Error: {error}</div>;

  return (
    <div>
      <h1>Companies</h1>
      <ul>
        {companies.map((company) => (
          <li key={company.id}>
            {company.company_name} ({company.company_email})
          </li>
        ))}
      </ul>
    </div>
  );
}

export default CompanyList;
```

### 2. Create Company Form

```jsx
import { useState } from 'react';

function CreateCompanyForm() {
  const [formData, setFormData] = useState({
    company_name: '',
    company_email: '',
    password: '',
    logo: '',
    website: '',
  });
  const [message, setMessage] = useState('');
  const [loading, setLoading] = useState(false);

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);

    try {
      const response = await fetch('http://localhost:8000/api/v1/company', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(formData),
      });

      const data = await response.json();

      if (!response.ok) {
        setMessage(`Error: ${data.detail}`);
        return;
      }

      setMessage(`✓ Company created! ID: ${data.id}`);
      setFormData({
        company_name: '',
        company_email: '',
        password: '',
        logo: '',
        website: '',
      });
    } catch (err) {
      setMessage(`Error: ${err.message}`);
    } finally {
      setLoading(false);
    }
  };

  const handleChange = (e) => {
    setFormData({
      ...formData,
      [e.target.name]: e.target.value,
    });
  };

  return (
    <form onSubmit={handleSubmit}>
      <h2>Create Company</h2>
      
      <input
        name="company_name"
        placeholder="Company Name"
        value={formData.company_name}
        onChange={handleChange}
        required
      />

      <input
        name="company_email"
        type="email"
        placeholder="Email"
        value={formData.company_email}
        onChange={handleChange}
        required
      />

      <input
        name="password"
        type="password"
        placeholder="Password"
        value={formData.password}
        onChange={handleChange}
        required
      />

      <input
        name="logo"
        placeholder="Logo URL (optional)"
        value={formData.logo}
        onChange={handleChange}
      />

      <input
        name="website"
        placeholder="Website (optional)"
        value={formData.website}
        onChange={handleChange}
      />

      <button type="submit" disabled={loading}>
        {loading ? 'Creating...' : 'Create'}
      </button>

      {message && <p>{message}</p>}
    </form>
  );
}

export default CreateCompanyForm;
```

### 3. API Key Manager

```jsx
import { useState, useEffect } from 'react';

function APIKeyManager({ companyId }) {
  const [keys, setKeys] = useState([]);
  const [newKeyName, setNewKeyName] = useState('');
  const [loading, setLoading] = useState(false);
  const [message, setMessage] = useState('');

  const API_KEY = process.env.REACT_APP_API_KEY;

  useEffect(() => {
    loadKeys();
  }, []);

  const loadKeys = async () => {
    try {
      const response = await fetch(
        `http://localhost:8000/api/v1/company/${companyId}/api-keys`,
        {
          headers: { 'X-API-Key': API_KEY },
        }
      );

      if (response.ok) {
        const data = await response.json();
        setKeys(data);
      }
    } catch (err) {
      setMessage(`Error loading keys: ${err.message}`);
    }
  };

  const handleCreateKey = async () => {
    if (!newKeyName.trim()) {
      setMessage('Please enter a key name');
      return;
    }

    setLoading(true);

    const expiryDate = new Date();
    expiryDate.setDate(expiryDate.getDate() + 90);

    try {
      const response = await fetch(
        `http://localhost:8000/api/v1/company/${companyId}/api-keys`,
        {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-API-Key': API_KEY,
          },
          body: JSON.stringify({
            name: newKeyName,
            expiry_date: expiryDate.toISOString(),
          }),
        }
      );

      const data = await response.json();

      if (!response.ok) {
        setMessage(`Error: ${data.detail}`);
        return;
      }

      setMessage(`✓ Key created! Store this: ${data.key}`);
      setNewKeyName('');
      loadKeys();
    } catch (err) {
      setMessage(`Error: ${err.message}`);
    } finally {
      setLoading(false);
    }
  };

  const handleRevokeKey = async (keyId) => {
    if (!window.confirm('Are you sure? This cannot be undone.')) return;

    try {
      await fetch(
        `http://localhost:8000/api/v1/company/${companyId}/api-keys/${keyId}/revoke`,
        {
          method: 'POST',
          headers: { 'X-API-Key': API_KEY },
        }
      );
      loadKeys();
      setMessage('✓ Key revoked');
    } catch (err) {
      setMessage(`Error: ${err.message}`);
    }
  };

  return (
    <div>
      <h2>API Keys</h2>

      <div>
        <input
          type="text"
          placeholder="New key name"
          value={newKeyName}
          onChange={(e) => setNewKeyName(e.target.value)}
          disabled={loading}
        />
        <button onClick={handleCreateKey} disabled={loading}>
          {loading ? 'Creating...' : 'Create Key'}
        </button>
      </div>

      {message && <p>{message}</p>}

      <table>
        <thead>
          <tr>
            <th>Name</th>
            <th>Preview</th>
            <th>Status</th>
            <th>Expires</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          {keys.map((key) => (
            <tr key={key.id}>
              <td>{key.name}</td>
              <td>{key.key_preview}</td>
              <td>{key.status}</td>
              <td>{new Date(key.expiry_date).toLocaleDateString()}</td>
              <td>
                <button onClick={() => handleRevokeKey(key.id)}>
                  Revoke
                </button>
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}

export default APIKeyManager;
```

### 4. Finetune Editor

```jsx
import { useState, useEffect } from 'react';

function FinetuneEditor({ companyId }) {
  const [content, setContent] = useState('');
  const [loading, setLoading] = useState(false);
  const [message, setMessage] = useState('');

  const API_KEY = process.env.REACT_APP_API_KEY;

  useEffect(() => {
    loadFinetune();
  }, []);

  const loadFinetune = async () => {
    try {
      const response = await fetch(
        `http://localhost:8000/api/v1/company/${companyId}/finetune`,
        {
          headers: { 'X-API-Key': API_KEY },
        }
      );

      if (response.ok) {
        const data = await response.json();
        setContent(data.content);
      }
    } catch (err) {
      setMessage(`Error loading: ${err.message}`);
    }
  };

  const handleSave = async () => {
    setLoading(true);
    setMessage('');

    try {
      const response = await fetch(
        `http://localhost:8000/api/v1/company/${companyId}/finetune`,
        {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-API-Key': API_KEY,
          },
          body: JSON.stringify({ content }),
        }
      );

      if (!response.ok) {
        const error = await response.json();
        setMessage(`Error: ${error.detail}`);
        return;
      }

      setMessage('✓ Finetune data saved!');
    } catch (err) {
      setMessage(`Error: ${err.message}`);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div>
      <h2>Knowledge Base Editor</h2>
      
      <textarea
        value={content}
        onChange={(e) => setContent(e.target.value)}
        placeholder="Enter markdown content..."
        rows={20}
        cols={80}
        disabled={loading}
      />

      <div>
        <button onClick={handleSave} disabled={loading}>
          {loading ? 'Saving...' : 'Save'}
        </button>
      </div>

      {message && <p>{message}</p>}
    </div>
  );
}

export default FinetuneEditor;
```

### 5. Custom Hook for API Calls

```javascript
// useAPI.js
import { useState, useEffect } from 'react';

function useAPI(endpoint, method = 'GET', body = null) {
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  const API_KEY = process.env.REACT_APP_API_KEY;
  const BASE_URL = process.env.REACT_APP_API_URL || 'http://localhost:8000';

  const execute = async (customBody = null) => {
    setLoading(true);
    setError(null);

    try {
      const options = {
        method,
        headers: {
          'Content-Type': 'application/json',
          'X-API-Key': API_KEY,
        },
      };

      if (customBody || body) {
        options.body = JSON.stringify(customBody || body);
      }

      const response = await fetch(`${BASE_URL}${endpoint}`, options);

      if (!response.ok) {
        const errorData = await response.json();
        throw new Error(errorData.detail || `HTTP ${response.status}`);
      }

      const result = await response.json();
      setData(result);
      return result;
    } catch (err) {
      setError(err.message);
      throw err;
    } finally {
      setLoading(false);
    }
  };

  return { data, loading, error, execute };
}

export default useAPI;

// Usage
function MyComponent() {
  const { data, loading, error, execute } = useAPI('/api/v1/company');

  useEffect(() => {
    execute();
  }, []);

  if (loading) return <div>Loading...</div>;
  if (error) return <div>Error: {error}</div>;

  return <div>{JSON.stringify(data)}</div>;
}
```

---

## Vue Examples

### 1. Company List with Vue 3

```vue
<template>
  <div>
    <h1>Companies</h1>
    
    <div v-if="loading">Loading...</div>
    <div v-else-if="error" class="error">Error: {{ error }}</div>
    
    <ul v-else>
      <li v-for="company in companies" :key="company.id">
        {{ company.company_name }} ({{ company.company_email }})
      </li>
    </ul>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue';

export default {
  setup() {
    const companies = ref([]);
    const loading = ref(true);
    const error = ref(null);
    const API_KEY = import.meta.env.VITE_API_KEY;

    const fetchCompanies = async () => {
      try {
        const response = await fetch('http://localhost:8000/api/v1/company', {
          headers: { 'X-API-Key': API_KEY },
        });

        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        
        companies.value = await response.json();
      } catch (err) {
        error.value = err.message;
      } finally {
        loading.value = false;
      }
    };

    onMounted(fetchCompanies);

    return { companies, loading, error };
  },
};
</script>
```

### 2. Create Company Form with Vue

```vue
<template>
  <form @submit.prevent="handleSubmit">
    <h2>Create Company</h2>
    
    <input
      v-model="form.company_name"
      type="text"
      placeholder="Company Name"
      required
    />

    <input
      v-model="form.company_email"
      type="email"
      placeholder="Email"
      required
    />

    <input
      v-model="form.password"
      type="password"
      placeholder="Password"
      required
    />

    <input
      v-model="form.logo"
      type="text"
      placeholder="Logo URL (optional)"
    />

    <input
      v-model="form.website"
      type="text"
      placeholder="Website (optional)"
    />

    <button type="submit" :disabled="loading">
      {{ loading ? 'Creating...' : 'Create' }}
    </button>

    <p v-if="message">{{ message }}</p>
  </form>
</template>

<script>
import { ref } from 'vue';

export default {
  setup() {
    const form = ref({
      company_name: '',
      company_email: '',
      password: '',
      logo: '',
      website: '',
    });
    const message = ref('');
    const loading = ref(false);
    const API_KEY = import.meta.env.VITE_API_KEY;

    const handleSubmit = async () => {
      loading.value = true;

      try {
        const response = await fetch('http://localhost:8000/api/v1/company', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(form.value),
        });

        const data = await response.json();

        if (!response.ok) {
          message.value = `Error: ${data.detail}`;
          return;
        }

        message.value = `✓ Company created! ID: ${data.id}`;
        Object.keys(form.value).forEach(key => {
          form.value[key] = '';
        });
      } catch (err) {
        message.value = `Error: ${err.message}`;
      } finally {
        loading.value = false;
      }
    };

    return { form, message, loading, handleSubmit };
  },
};
</script>
```

---

## TypeScript/Node.js Examples

### 1. Complete API Service

```typescript
// apiService.ts
import axios, { AxiosInstance } from 'axios';

interface Company {
  id: number;
  company_name: string;
  company_email: string;
  password: string;
  logo: string | null;
  website: string | null;
  created_at: string;
  updated_at: string;
}

interface APIKey {
  id: number;
  company_id: number;
  name: string;
  key_preview: string;
  status: 'active' | 'revoked' | 'expired';
  expiry_date: string;
  last_used_at: string | null;
  created_at: string;
  updated_at: string;
}

class PeraiAPI {
  private api: AxiosInstance;
  private apiKey: string;

  constructor(apiKey: string, baseURL = 'http://localhost:8000') {
    this.apiKey = apiKey;
    this.api = axios.create({
      baseURL,
      headers: {
        'Content-Type': 'application/json',
        'X-API-Key': apiKey,
      },
    });

    // Add response interceptor for error handling
    this.api.interceptors.response.use(
      (response) => response,
      (error) => {
        const message = error.response?.data?.detail || error.message;
        console.error('API Error:', message);
        throw new Error(message);
      }
    );
  }

  // Company methods
  async createCompany(data: Partial<Company>): Promise<Company> {
    const response = await this.api.post('/api/v1/company', data);
    return response.data;
  }

  async listCompanies(): Promise<Company[]> {
    const response = await this.api.get('/api/v1/company');
    return response.data;
  }

  async getCompany(id: number): Promise<Company> {
    const response = await this.api.get(`/api/v1/company/${id}`);
    return response.data;
  }

  async updateCompany(id: number, data: Partial<Company>): Promise<Company> {
    const response = await this.api.put(`/api/v1/company/${id}`, data);
    return response.data;
  }

  // API Key methods
  async createAPIKey(
    companyId: number,
    name: string,
    expiryDate: string
  ): Promise<APIKey & { key: string }> {
    const response = await this.api.post(
      `/api/v1/company/${companyId}/api-keys`,
      {
        name,
        expiry_date: expiryDate,
      }
    );
    return response.data;
  }

  async listAPIKeys(companyId: number): Promise<APIKey[]> {
    const response = await this.api.get(
      `/api/v1/company/${companyId}/api-keys`
    );
    return response.data;
  }

  async getAPIKey(companyId: number, keyId: number): Promise<APIKey> {
    const response = await this.api.get(
      `/api/v1/company/${companyId}/api-keys/${keyId}`
    );
    return response.data;
  }

  async updateAPIKey(
    companyId: number,
    keyId: number,
    data: Partial<APIKey>
  ): Promise<APIKey> {
    const response = await this.api.put(
      `/api/v1/company/${companyId}/api-keys/${keyId}`,
      data
    );
    return response.data;
  }

  async revokeAPIKey(companyId: number, keyId: number): Promise<APIKey> {
    const response = await this.api.post(
      `/api/v1/company/${companyId}/api-keys/${keyId}/revoke`
    );
    return response.data;
  }

  async deleteAPIKey(companyId: number, keyId: number): Promise<void> {
    await this.api.delete(`/api/v1/company/${companyId}/api-keys/${keyId}`);
  }

  // Finetune methods
  async uploadFinetune(
    companyId: number,
    content: string
  ): Promise<{ id: number; company_id: number; rag_company_path: string }> {
    const response = await this.api.post(
      `/api/v1/company/${companyId}/finetune`,
      { content }
    );
    return response.data;
  }

  async getFinetune(companyId: number): Promise<{ content: string }> {
    const response = await this.api.get(
      `/api/v1/company/${companyId}/finetune`
    );
    return response.data;
  }
}

export default PeraiAPI;

// Usage
const api = new PeraiAPI(process.env.API_KEY);

async function main() {
  try {
    // Create company
    const company = await api.createCompany({
      company_name: 'Acme Corp',
      company_email: 'contact@acme.com',
      password: 'SecurePass123!',
    });
    console.log('Company created:', company.id);

    // Create API key
    const expiryDate = new Date();
    expiryDate.setDate(expiryDate.getDate() + 90);

    const apiKey = await api.createAPIKey(
      company.id,
      'prod_key',
      expiryDate.toISOString()
    );
    console.log('API Key:', apiKey.key);

    // Upload finetune
    await api.uploadFinetune(
      company.id,
      '# Company KB\n\n## Services\n- Service A'
    );

    // List API keys
    const keys = await api.listAPIKeys(company.id);
    console.log('Keys:', keys);
  } catch (error) {
    console.error('Error:', error.message);
  }
}

main();
```

---

## cURL Examples

### Setup
```bash
# Set variables
COMPANY_ID=1
API_KEY="sk_a99KGHR57bhNzRv5vhnR-X8jhtoqm1qiIclDt2urUG8"
BASE_URL="http://localhost:8000"

# Export for easy use
export API_KEY
export BASE_URL
export COMPANY_ID
```

### Company Operations
```bash
# Create company
curl -X POST $BASE_URL/api/v1/company \
  -H "Content-Type: application/json" \
  -d '{
    "company_name": "Acme Corp",
    "company_email": "contact@acme.com",
    "password": "SecurePass123!",
    "website": "https://acme.com"
  }' | jq .

# List companies
curl $BASE_URL/api/v1/company \
  -H "X-API-Key: $API_KEY" | jq .

# Get company
curl $BASE_URL/api/v1/company/$COMPANY_ID \
  -H "X-API-Key: $API_KEY" | jq .

# Update company
curl -X PUT $BASE_URL/api/v1/company/$COMPANY_ID \
  -H "Content-Type: application/json" \
  -H "X-API-Key: $API_KEY" \
  -d '{"website": "https://newsite.com"}' | jq .
```

### API Key Operations
```bash
# Create API key
curl -X POST $BASE_URL/api/v1/company/$COMPANY_ID/api-keys \
  -H "Content-Type: application/json" \
  -H "X-API-Key: $API_KEY" \
  -d '{
    "name": "prod_key",
    "expiry_date": "2026-08-22T00:00:00"
  }' | jq .

# List API keys
curl $BASE_URL/api/v1/company/$COMPANY_ID/api-keys \
  -H "X-API-Key: $API_KEY" | jq .

# Get API key
curl $BASE_URL/api/v1/company/$COMPANY_ID/api-keys/1 \
  -H "X-API-Key: $API_KEY" | jq .

# Revoke API key
curl -X POST $BASE_URL/api/v1/company/$COMPANY_ID/api-keys/1/revoke \
  -H "X-API-Key: $API_KEY" | jq .

# Delete API key
curl -X DELETE $BASE_URL/api/v1/company/$COMPANY_ID/api-keys/1 \
  -H "X-API-Key: $API_KEY" | jq .
```

### Finetune Operations
```bash
# Upload finetune
curl -X POST $BASE_URL/api/v1/company/$COMPANY_ID/finetune \
  -H "Content-Type: application/json" \
  -H "X-API-Key: $API_KEY" \
  -d '{
    "content": "# Company KB\n\n## Services\n- Service A"
  }' | jq .

# Get finetune
curl $BASE_URL/api/v1/company/$COMPANY_ID/finetune \
  -H "X-API-Key: $API_KEY" | jq .

# Save to file
curl $BASE_URL/api/v1/company/$COMPANY_ID/finetune \
  -H "X-API-Key: $API_KEY" | jq -r '.content' > company_kb.md
```

---

**Last Updated**: 2026-05-22
**Version**: 1.0
