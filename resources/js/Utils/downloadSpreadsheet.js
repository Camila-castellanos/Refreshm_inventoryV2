import * as XLSX from 'xlsx';

function downloadSelectedKeysSpreadsheet(data, keysToDownload, filename = 'spreadsheet.xlsx') {
  if (!Array.isArray(data) || data.length === 0) {
    console.error("Input data must be a non-empty array of objects.");
    throw new Error("Select at least one item to download spreadsheet")
  }

  if (!Array.isArray(keysToDownload) || keysToDownload.length === 0) {
    console.warn("No keys specified for download. Downloading all available keys.");
    return downloadSpreadsheet(data, filename); // Fallback to downloading all keys
  }

  // Create a new workbook
  const workbook = XLSX.utils.book_new();

  // Filter the keys to ensure they exist in the data objects
  const validKeys = keysToDownload.filter(key => key in data[0]);

  if (validKeys.length === 0) {
    console.error("None of the specified keys found in the data objects.");
    return;
  }

  // Convert the array of objects to an array of arrays with only the selected keys
  const worksheetData = [
    validKeys, // Use the selected keys as headers
    ...data.map(obj => validKeys.map(key => obj[key])),
  ];

  // Create a new worksheet
  const worksheet = XLSX.utils.aoa_to_sheet(worksheetData);

  // Add the worksheet to the workbook
  XLSX.utils.book_append_sheet(workbook, worksheet, 'Sheet1');

  // Generate the Excel file and trigger the download
  XLSX.writeFile(workbook, filename);
}

// Helper function to download all keys (reused from previous example)
function downloadSpreadsheet(data, filename = 'spreadsheet.xlsx') {
  if (!Array.isArray(data) || data.length === 0) {
    console.error("Input data must be a non-empty array of objects.");
    return;
  }
  const workbook = XLSX.utils.book_new();
  const headers = Object.keys(data[0]);
  const worksheetData = [headers, ...data.map(obj => headers.map(key => obj[key]))];
  const worksheet = XLSX.utils.aoa_to_sheet(worksheetData);
  XLSX.utils.book_append_sheet(workbook, worksheet, 'Sheet1');
  XLSX.writeFile(workbook, filename);
}

export default downloadSelectedKeysSpreadsheet;