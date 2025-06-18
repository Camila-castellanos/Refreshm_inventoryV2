// This file contains utility functions for formatting various types of data.

export function formatPercentage(value) {
  return Number.isInteger(value)
    ? `${value}%`
    : `${value.toFixed(2)}%`
}