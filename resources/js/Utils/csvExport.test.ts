import { describe, it, expect, vi } from 'vitest';
import { exportToCSV, generateCSVString } from './csvExport';

describe('csvExport', () => {
  describe('generateCSVString', () => {
    it('generates a CSV string with headers and mapped rows', () => {
      const data = [
        { id: 1, name: 'John Doe', age: 30 },
        { id: 2, name: 'Jane Doe', age: 25 },
      ];
      const config = {
        headers: ['ID', 'Name', 'Age'],
        rowMapper: (item: any) => [item.id, item.name, item.age],
      };

      const csv = generateCSVString(data, config);
      const lines = csv.split('\n');
      
      expect(lines[0]).toBe('"ID","Name","Age"');
      expect(lines[1]).toBe('"1","John Doe","30"');
      expect(lines[2]).toBe('"2","Jane Doe","25"');
    });

    it('handles special characters by escaping them', () => {
      const data = [{ text: 'value with "quotes" and , comma' }];
      const config = {
        headers: ['Text'],
        rowMapper: (item: any) => [item.text],
      };

      const csv = generateCSVString(data, config);
      const lines = csv.split('\n');
      expect(lines[1]).toBe('"value with ""quotes"" and , comma"');
    });

    it('handles null and undefined values as empty strings', () => {
      const data = [{ val1: null, val2: undefined }];
      const config = {
        headers: ['Val1', 'Val2'],
        rowMapper: (item: any) => [item.val1, item.val2],
      };

      const csv = generateCSVString(data, config);
      const lines = csv.split('\n');
      expect(lines[1]).toBe('"",""');
    });

    it('returns only headers when data array is empty', () => {
      const data: any[] = [];
      const config = {
        headers: ['ID', 'Name'],
        rowMapper: (item: any) => [item.id, item.name],
      };

      const csv = generateCSVString(data, config);
      expect(csv).toBe('"ID","Name"');
    });
  });

  describe('exportToCSV', () => {
    it('calls generateCSVString and triggers a download (mocked)', () => {
      // In a real browser environment, we'd check for document.createElement etc.
      // For unit tests, we'll just ensure it doesn't crash or we can mock the global functions.
      
      const data = [{ id: 1 }];
      const config = {
        headers: ['ID'],
        rowMapper: (item: any) => [item.id],
      };

      // Mocking global URL and document
      global.URL.createObjectURL = vi.fn(() => 'blob:url');
      global.URL.revokeObjectURL = vi.fn();
      
      const link = {
        setAttribute: vi.fn(),
        click: vi.fn(),
        style: {},
      };
      vi.spyOn(document, 'createElement').mockReturnValue(link as any);
      vi.spyOn(document.body, 'appendChild').mockImplementation(() => ({} as any));
      vi.spyOn(document.body, 'removeChild').mockImplementation(() => ({} as any));

      exportToCSV(data, config);

      expect(document.createElement).toHaveBeenCalledWith('a');
      expect(link.setAttribute).toHaveBeenCalledWith('download', expect.stringContaining('.csv'));
      expect(link.click).toHaveBeenCalled();
    });
  });
});
