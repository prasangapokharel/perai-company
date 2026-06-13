export type EmbedOptions = {
  widgetOrigin: string
  apiBaseUrl: string
  companyId: number
  apiKey: string
  title?: string
  color?: string
}

export function buildEmbedScript(options: EmbedOptions): string {
  const {
    widgetOrigin,
    apiBaseUrl,
    companyId,
    apiKey,
    title = "Chat with us",
    color = "#2563eb",
  } = options

  const scriptSrc = `${widgetOrigin.replace(/\/$/, "")}/widget.js`
  const logoUrl = `${apiBaseUrl.replace(/\/$/, "")}/files/companies/${companyId}/logo`

  return `<script
  src="${scriptSrc}"
  data-company-id="${companyId}"
  data-api-key="${apiKey}"
  data-api-base="${apiBaseUrl.replace(/\/$/, "")}"
  data-logo-url="${logoUrl}"
  data-title="${title}"
  data-color="${color}"
  async
></script>`
}

export function buildIframeEmbed(options: EmbedOptions & { height?: number }): string {
  const { widgetOrigin, companyId, apiKey, apiBaseUrl, title = "Chat", height = 520 } = options
  const src = new URL("/widget/frame", widgetOrigin.replace(/\/$/, ""))
  src.searchParams.set("companyId", String(companyId))
  src.searchParams.set("apiKey", apiKey)
  src.searchParams.set("apiBase", apiBaseUrl.replace(/\/$/, ""))
  src.searchParams.set("title", title)

  return `<iframe
  src="${src.toString()}"
  title="${title}"
  width="100%"
  height="${height}"
  style="border:0;border-radius:12px;"
  loading="lazy"
></iframe>`
}
