import { jsPDF } from 'jspdf'
import autoTable from 'jspdf-autotable'
import * as XLSX from 'xlsx'

// Simple date formatter
const formatDate = (date, formatStr = 'full') => {
  const d = new Date(date)
  const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
  const monthsFr = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Août', 'Sep', 'Oct', 'Nov', 'Déc']
  
  if (formatStr === 'full') {
    return `${months[d.getMonth()]} ${d.getDate()}, ${d.getFullYear()}`
  } else if (formatStr === 'short') {
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`
  } else if (formatStr === 'time') {
    return `${String(d.getMonth() + 1).padStart(2, '0')}/${String(d.getDate()).padStart(2, '0')}/${d.getFullYear()} ${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}`
  }
  return d.toLocaleDateString()
}

/**
 * Export Analytics Report to PDF with professional template
 */
export const exportToPDF = (data, locale = 'en') => {
  try {
    const doc = new jsPDF()
  const pageWidth = doc.internal.pageSize.getWidth()
  const pageHeight = doc.internal.pageSize.getHeight()
  
  // Colors
  const primaryColor = [0, 76, 151] // #004C97
  const accentColor = [254, 198, 1] // #FEC601
  const textColor = [34, 40, 49] // #222831
  const lightGray = [244, 246, 250] // #F4F6FA
  
  // Header with logo and title
  doc.setFillColor(...primaryColor)
  doc.rect(0, 0, pageWidth, 40, 'F')
  
  // Logo/Title
  doc.setTextColor(255, 255, 255)
  doc.setFontSize(24)
  doc.setFont('helvetica', 'bold')
  doc.text('SOMA', 20, 20)
  
  doc.setFontSize(12)
  doc.setFont('helvetica', 'normal')
  doc.text(locale === 'fr' ? 'Surveillance PV' : 'PV Monitoring', 20, 28)
  
  // Report Title
  doc.setFontSize(18)
  doc.setFont('helvetica', 'bold')
  const reportTitle = locale === 'fr' ? 'Rapport d\'Analyse' : 'Analytics Report'
  doc.text(reportTitle, pageWidth - 20, 25, { align: 'right' })
  
  // Date
  doc.setFontSize(10)
  doc.setFont('helvetica', 'normal')
  const dateText = formatDate(new Date(), 'full')
  doc.text(dateText, pageWidth - 20, 32, { align: 'right' })
  
  let yPosition = 50
  
  // KPI Summary Section
  doc.setFillColor(...lightGray)
  doc.rect(15, yPosition, pageWidth - 30, 8, 'F')
  
  doc.setTextColor(...primaryColor)
  doc.setFontSize(14)
  doc.setFont('helvetica', 'bold')
  doc.text(locale === 'fr' ? 'Résumé des KPI' : 'KPI Summary', 20, yPosition + 6)
  
  yPosition += 15
  
  // KPI Cards
  const kpis = [
    {
      label: locale === 'fr' ? 'Amelioration Moyenne' : 'Avg. Efficiency Improvement',
      value: data?.kpis?.avgImprovement || '12.1%',
      symbol: '^'
    },
    {
      label: locale === 'fr' ? 'Panneaux Critiques' : 'Critical Panels',
      value: data?.kpis?.criticalPanels || '7',
      symbol: '!'
    },
    {
      label: locale === 'fr' ? 'Score d\'Impact' : 'Maintenance Impact Score',
      value: data?.kpis?.maintenanceImpact || '85.4%',
      symbol: '*'
    }
  ]
  
  kpis.forEach((kpi, index) => {
    const xPos = 20 + (index * 60)
    
    doc.setFillColor(255, 255, 255)
    doc.setDrawColor(...primaryColor)
    doc.roundedRect(xPos, yPosition, 55, 25, 3, 3, 'FD')
    
    // Draw colored circle with symbol
    doc.setFillColor(...primaryColor)
    doc.circle(xPos + 8, yPosition + 10, 4, 'F')
    
    doc.setTextColor(255, 255, 255)
    doc.setFontSize(12)
    doc.setFont('helvetica', 'bold')
    doc.text(kpi.symbol, xPos + 6.5, yPosition + 12)
    
    doc.setTextColor(...textColor)
    doc.setFontSize(16)
    doc.setFont('helvetica', 'bold')
    doc.text(kpi.value, xPos + 15, yPosition + 12)
    
    doc.setFontSize(8)
    doc.setFont('helvetica', 'normal')
    doc.setTextColor(100, 100, 100)
    const lines = doc.splitTextToSize(kpi.label, 50)
    doc.text(lines, xPos + 5, yPosition + 18)
  })
  
  yPosition += 35
  
  // Performance Table Section
  doc.setFillColor(...lightGray)
  doc.rect(15, yPosition, pageWidth - 30, 8, 'F')
  
  doc.setTextColor(...primaryColor)
  doc.setFontSize(14)
  doc.setFont('helvetica', 'bold')
  doc.text(locale === 'fr' ? 'Performance des Panneaux' : 'Panel Performance', 20, yPosition + 6)
  
  yPosition += 12
  
  // Table data
  const tableHeaders = locale === 'fr' 
    ? ['Panneau', 'Efficacité', 'Température', 'Production', 'Santé']
    : ['Panel ID', 'Efficiency', 'Temperature', 'Output', 'Health']
  
  const tableData = data?.performanceTable || [
    ['PV-001', '94.2%', '45°C', '285W', '98%'],
    ['PV-045', '87.5%', '52°C', '268W', '85%'],
    ['PV-089', '76.3%', '58°C', '245W', '72%'],
    ['PV-123', '91.8%', '47°C', '278W', '95%'],
    ['PV-207', '88.9%', '49°C', '271W', '89%']
  ]
  
  autoTable(doc, {
    startY: yPosition,
    head: [tableHeaders],
    body: tableData,
    theme: 'striped',
    headStyles: {
      fillColor: primaryColor,
      textColor: [255, 255, 255],
      fontSize: 10,
      fontStyle: 'bold',
      halign: 'center'
    },
    bodyStyles: {
      fontSize: 9,
      halign: 'center'
    },
    alternateRowStyles: {
      fillColor: [249, 250, 251]
    },
    margin: { left: 20, right: 20 }
  })
  
  // Get the final Y position after the table
  const finalY = doc.lastAutoTable ? doc.lastAutoTable.finalY : yPosition + 80
  yPosition = finalY + 15
  
  // Efficiency Trend Section
  if (yPosition > pageHeight - 60) {
    doc.addPage()
    yPosition = 20
  }
  
  doc.setFillColor(...lightGray)
  doc.rect(15, yPosition, pageWidth - 30, 8, 'F')
  
  doc.setTextColor(...primaryColor)
  doc.setFontSize(14)
  doc.setFont('helvetica', 'bold')
  doc.text(locale === 'fr' ? 'Tendance d\'Efficacité' : 'Efficiency Trend', 20, yPosition + 6)
  
  yPosition += 15
  
  // Chart - Draw efficiency trend
  const chartX = 25
  const chartY = yPosition + 5
  const chartWidth = pageWidth - 50
  const chartHeight = 40
  
  // Chart background
  doc.setFillColor(255, 255, 255)
  doc.rect(chartX, chartY, chartWidth, chartHeight, 'F')
  
  // Chart border
  doc.setDrawColor(200, 200, 200)
  doc.setLineWidth(0.3)
  doc.rect(chartX, chartY, chartWidth, chartHeight)
  
  // Grid lines
  doc.setDrawColor(240, 240, 240)
  doc.setLineWidth(0.2)
  for (let i = 1; i <= 4; i++) {
    const y = chartY + (chartHeight / 5) * i
    doc.line(chartX, y, chartX + chartWidth, y)
  }
  
  // Sample data points (Before and After Maintenance)
  const dataPoints = 8
  const beforeData = [78, 76, 77, 75, 79, 77, 78, 76]
  const afterData = [89, 88, 90, 87, 91, 89, 90, 88]
  
  // Draw "Before Maintenance" line (red)
  doc.setDrawColor(220, 53, 69)
  doc.setLineWidth(1.5)
  for (let i = 0; i < dataPoints - 1; i++) {
    const x1 = chartX + (chartWidth / (dataPoints - 1)) * i
    const x2 = chartX + (chartWidth / (dataPoints - 1)) * (i + 1)
    const y1 = chartY + chartHeight - ((beforeData[i] - 70) / 25 * chartHeight)
    const y2 = chartY + chartHeight - ((beforeData[i + 1] - 70) / 25 * chartHeight)
    doc.line(x1, y1, x2, y2)
  }
  
  // Draw "After Maintenance" line (green)
  doc.setDrawColor(40, 167, 69)
  doc.setLineWidth(1.5)
  for (let i = 0; i < dataPoints - 1; i++) {
    const x1 = chartX + (chartWidth / (dataPoints - 1)) * i
    const x2 = chartX + (chartWidth / (dataPoints - 1)) * (i + 1)
    const y1 = chartY + chartHeight - ((afterData[i] - 70) / 25 * chartHeight)
    const y2 = chartY + chartHeight - ((afterData[i + 1] - 70) / 25 * chartHeight)
    doc.line(x1, y1, x2, y2)
  }
  
  // Legend
  const legendY = chartY + chartHeight + 8
  doc.setFontSize(8)
  
  // Before legend
  doc.setDrawColor(220, 53, 69)
  doc.setLineWidth(1.5)
  doc.line(chartX, legendY, chartX + 8, legendY)
  doc.setTextColor(100, 100, 100)
  doc.text(locale === 'fr' ? 'Avant maintenance' : 'Before Maintenance', chartX + 10, legendY + 1)
  
  // After legend
  doc.setDrawColor(40, 167, 69)
  doc.line(chartX + 60, legendY, chartX + 68, legendY)
  doc.text(locale === 'fr' ? 'Apres maintenance' : 'After Maintenance', chartX + 70, legendY + 1)
  
  yPosition = legendY + 5
  
  // Footer
  const footerY = pageHeight - 15
  doc.setDrawColor(...primaryColor)
  doc.setLineWidth(0.5)
  doc.line(20, footerY - 5, pageWidth - 20, footerY - 5)
  
  doc.setTextColor(...textColor)
  doc.setFontSize(8)
  doc.setFont('helvetica', 'normal')
  doc.text('© 2025 SOMA Platform', 20, footerY)
  doc.text(
    locale === 'fr' 
      ? `Page 1 | Généré le ${formatDate(new Date(), 'time')}`
      : `Page 1 | Generated on ${formatDate(new Date(), 'time')}`,
    pageWidth - 20,
    footerY,
    { align: 'right' }
  )
  
  // Save PDF
  const fileName = `SOMA_Analytics_Report_${formatDate(new Date(), 'short')}.pdf`
  doc.save(fileName)
  } catch (error) {
    console.error('PDF Export Error:', error)
    throw error
  }
}

/**
 * Export Analytics Report to Excel with professional formatting
 */
export const exportToExcel = (data, locale = 'en') => {
  try {
    // Create workbook
    const wb = XLSX.utils.book_new()
  
  // Sheet 1: Summary
  const summaryData = [
    [locale === 'fr' ? 'RAPPORT D\'ANALYSE SOMA' : 'SOMA ANALYTICS REPORT'],
    [],
    [locale === 'fr' ? 'Date de génération:' : 'Generated on:', formatDate(new Date(), 'full')],
    [],
    [locale === 'fr' ? 'RÉSUMÉ DES KPI' : 'KPI SUMMARY'],
    [],
    [locale === 'fr' ? 'Métrique' : 'Metric', locale === 'fr' ? 'Valeur' : 'Value'],
    [locale === 'fr' ? 'Amélioration Moyenne de l\'Efficacité' : 'Average Efficiency Improvement', data?.kpis?.avgImprovement || '12.1%'],
    [locale === 'fr' ? 'Panneaux Critiques' : 'Critical Panels', data?.kpis?.criticalPanels || '7'],
    [locale === 'fr' ? 'Score d\'Impact de Maintenance' : 'Maintenance Impact Score', data?.kpis?.maintenanceImpact || '85.4%'],
    [locale === 'fr' ? 'Durée de Vie Restante Moyenne' : 'Average Remaining Useful Life', data?.kpis?.avgRUL || '8.2 years']
  ]
  
  const ws1 = XLSX.utils.aoa_to_sheet(summaryData)
  
  // Style the summary sheet
  ws1['!cols'] = [{ wch: 40 }, { wch: 20 }]
  ws1['!rows'] = [{ hpt: 25 }]
  
  XLSX.utils.book_append_sheet(wb, ws1, locale === 'fr' ? 'Résumé' : 'Summary')
  
  // Sheet 2: Performance Table
  const performanceHeaders = locale === 'fr'
    ? ['ID Panneau', 'Efficacité (%)', 'Température (°C)', 'Production (W)', 'Score de Santé (%)']
    : ['Panel ID', 'Efficiency (%)', 'Temperature (°C)', 'Output (W)', 'Health Score (%)']
  
  // Ensure performance data is in correct format
  let performanceData = []
  if (data?.performanceTable && Array.isArray(data.performanceTable)) {
    performanceData = data.performanceTable.map(row => {
      if (Array.isArray(row)) {
        return row.map(cell => {
          // Remove % and other symbols for numeric values
          if (typeof cell === 'string') {
            const numMatch = cell.match(/[\d.]+/)
            if (numMatch && (cell.includes('%') || cell.includes('W') || cell.includes('°C'))) {
              return parseFloat(numMatch[0])
            }
          }
          return cell
        })
      }
      return row
    })
  } else {
    // Default data
    performanceData = [
      ['PV-001', 94.2, 45, 285, 98],
      ['PV-045', 87.5, 52, 268, 85],
      ['PV-089', 76.3, 58, 245, 72],
      ['PV-123', 91.8, 47, 278, 95],
      ['PV-207', 88.9, 49, 271, 89]
    ]
  }
  
  const ws2 = XLSX.utils.aoa_to_sheet([performanceHeaders, ...performanceData])
  ws2['!cols'] = [{ wch: 12 }, { wch: 15 }, { wch: 18 }, { wch: 15 }, { wch: 18 }]
  
  XLSX.utils.book_append_sheet(wb, ws2, locale === 'fr' ? 'Performance' : 'Performance')
  
  // Sheet 3: Efficiency Trend
  const trendHeaders = locale === 'fr'
    ? ['Date', 'Avant Maintenance (%)', 'Après Maintenance (%)']
    : ['Date', 'Before Maintenance (%)', 'After Maintenance (%)']
  
  let trendData = []
  if (data?.efficiencyTrend?.labels && Array.isArray(data.efficiencyTrend.labels)) {
    trendData = data.efficiencyTrend.labels.map((label, index) => [
      label || `Point ${index + 1}`,
      data.efficiencyTrend.datasets?.[0]?.data?.[index] || 0,
      data.efficiencyTrend.datasets?.[1]?.data?.[index] || 0
    ])
  } else {
    trendData = [
      ['Jan 1', 78, 89],
      ['Jan 8', 76, 88],
      ['Jan 15', 77, 90],
      ['Jan 22', 75, 87],
      ['Jan 29', 79, 91]
    ]
  }
  
  const ws3 = XLSX.utils.aoa_to_sheet([trendHeaders, ...trendData])
  ws3['!cols'] = [{ wch: 12 }, { wch: 25 }, { wch: 25 }]
  
  XLSX.utils.book_append_sheet(wb, ws3, locale === 'fr' ? 'Tendance' : 'Trend')
  
  // Sheet 4: Dust Accumulation
  const dustHeaders = locale === 'fr'
    ? ['Date', 'Niveau de Poussière (%)']
    : ['Date', 'Dust Level (%)']
  
  let dustData = []
  if (data?.dustAccumulation?.labels && Array.isArray(data.dustAccumulation.labels)) {
    dustData = data.dustAccumulation.labels.map((label, index) => [
      label || `Point ${index + 1}`,
      data.dustAccumulation.datasets?.[0]?.data?.[index] || 0
    ])
  } else {
    dustData = [
      ['Oct 1', 2],
      ['Oct 8', 5],
      ['Oct 15', 8],
      ['Oct 22', 12],
      ['Oct 29', 15]
    ]
  }
  
  const ws4 = XLSX.utils.aoa_to_sheet([dustHeaders, ...dustData])
  ws4['!cols'] = [{ wch: 12 }, { wch: 20 }]
  
  XLSX.utils.book_append_sheet(wb, ws4, locale === 'fr' ? 'Poussière' : 'Dust')
  
  // Save Excel file
  const fileName = `SOMA_Analytics_Report_${formatDate(new Date(), 'short')}.xlsx`
  XLSX.writeFile(wb, fileName)
  } catch (error) {
    console.error('Excel Export Error:', error)
    throw error
  }
}
