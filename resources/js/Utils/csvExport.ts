export interface CSVConfig<T> {
  headers: string[];
  rowMapper: (item: T) => (string | number | boolean | null | undefined)[];
  filenamePrefix?: string;
}

/**
 * Formats a single row of data into a CSV-compliant string.
 */
function formatCSVRow(row: (string | number | boolean | null | undefined)[]): string {
  return row
    .map((val) => {
      const stringVal = val === null || val === undefined ? '' : String(val);
      const escaped = stringVal.replace(/"/g, '""');
      return `"${escaped}"`;
    })
    .join(',');
}

/**
 * Generates the full CSV content string from data and configuration.
 */
export function generateCSVString<T>(data: T[], config: CSVConfig<T>): string {
  const headerRow = formatCSVRow(config.headers);
  const dataRows = data.map((item) => formatCSVRow(config.rowMapper(item)));
  return [headerRow, ...dataRows].join('\n');
}

/**
 * Generates a CSV file from data and triggers a browser download.
 */
export function exportToCSV<T>(data: T[], config: CSVConfig<T>): void {
  const csvContent = generateCSVString(data, config);
  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
  const url = URL.createObjectURL(blob);
  const link = document.createElement('a');
  
  const date = new Date().toISOString().split('T')[0];
  const filename = `${config.filenamePrefix || 'export'}_${date}.csv`;
  
  link.setAttribute('href', url);
  link.setAttribute('download', filename);
  link.style.visibility = 'hidden';
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
  URL.revokeObjectURL(url);
}
