# R3 - Utilización de Muelles - Quick Start Guide

## 🎯 What is R3?

R3 (Utilización de Muelles) is a comprehensive berth utilization analysis report that calculates:
- **Hourly utilization** of each berth based on vessel berthing times
- **Window conflicts** (overlapping vessel calls)
- **Idle hours** (periods with <10% utilization)
- **Peak utilization** across all time slots

## 🚀 Accessing R3

### URL
```
http://localhost:8000/reports/port/berth-utilization
```

### Required Permission
- `PORT_REPORT_READ`

### Authorized Roles
- ADMIN
- PLANIFICADOR_PUERTO
- OPERACIONES_PUERTO
- ANALISTA
- AUDITOR

## 🔧 Using the Report

### 1. Apply Filters

**Date Range:**
- Fecha Desde: Start date/time
- Fecha Hasta: End date/time

**Berth Filter:**
- Select specific berth or "Todos" for all berths

**Time Slot Configuration:**
- 1 hora (default)
- 2 horas
- 4 horas
- 6 horas

### 2. View KPIs

**Utilización Promedio:**
- Average utilization across all time slots
- Green: Good utilization
- Yellow: Moderate
- Red: High congestion risk

**Conflictos de Ventana:**
- Number of overlapping vessel calls
- 0 = No conflicts (ideal)
- >0 = Scheduling issues detected

**Horas Ociosas:**
- Total hours with <10% utilization
- Indicates underutilized capacity

**Utilización Máxima:**
- Peak utilization observed
- ≥85% = High congestion risk

### 3. Analyze Utilization by Time Slot

**Visual Progress Bars:**
- Green: 10-50% (Low utilization)
- Yellow: 50-85% (Medium utilization)
- Red: ≥85% (High utilization - congestion risk)
- Gray: <10% (Idle)

**Status Badges:**
- Alta: ≥85% utilization
- Media: 50-85% utilization
- Baja: 10-50% utilization
- Ociosa: <10% utilization

### 4. Review Vessel Call Details

Table shows:
- Vessel name
- Voyage ID
- Berth assignment
- ATB (Actual Time of Berthing)
- ATD (Actual Time of Departure)
- Permanence duration (hours)
- Call status

### 5. Export Data

**Available Formats:**
- CSV: Raw data for analysis
- XLSX: Excel format with formatting
- PDF: Printable report

**Requirements:**
- Must have `REPORT_EXPORT` permission
- Rate limited to 5 exports per minute

## 📊 Understanding the KPIs

### Utilization Calculation

```
Utilization = (Occupied Hours / Total Slot Hours) × 100
```

**Example:**
- Time slot: 10:00-11:00 (1 hour)
- Vessel berthed: 10:00-10:30 (30 minutes)
- Utilization: 50%

### Conflict Detection

A conflict occurs when:
```
Vessel A ATD > Vessel B ATB (same berth)
```

**Example:**
- Vessel A: 10:00-12:00
- Vessel B: 11:00-13:00
- Result: 1 conflict (1-hour overlap)

**Not a Conflict:**
- Vessel A: 10:00-12:00
- Vessel B: 12:00-14:00
- Result: 0 conflicts (consecutive, no overlap)

### Idle Hours

Counts time slots where utilization < 10%

**Example:**
- 4 slots of 1 hour each
- Utilizations: [100%, 50%, 0%, 0%]
- Idle hours: 2 hours (the two 0% slots)

## 🎨 Visual Indicators

### Utilization Colors
- 🟢 Green (10-50%): Optimal utilization
- 🟡 Yellow (50-85%): Good utilization
- 🔴 Red (≥85%): Congestion risk
- ⚪ Gray (<10%): Underutilized

### Status Badges
- 🔴 Alta: Immediate attention needed
- 🟡 Media: Monitor closely
- 🟢 Baja: Normal operations
- 🔵 Ociosa: Capacity available

## 💡 Use Cases

### 1. Capacity Planning
**Goal:** Identify underutilized berths
**Action:** Look for high "Horas Ociosas"
**Result:** Optimize berth assignments

### 2. Congestion Prevention
**Goal:** Avoid scheduling conflicts
**Action:** Monitor "Conflictos de Ventana"
**Result:** Adjust vessel schedules

### 3. Peak Hour Analysis
**Goal:** Identify busy periods
**Action:** Review time slots with ≥85% utilization
**Result:** Plan additional resources

### 4. Performance Benchmarking
**Goal:** Compare berth efficiency
**Action:** Compare "Utilización Promedio" across berths
**Result:** Identify best practices

## 🔍 Troubleshooting

### No Data Displayed
**Cause:** No vessel calls with ATB and ATD in date range
**Solution:** Adjust date filters or check vessel call data

### High Conflict Count
**Cause:** Overlapping vessel schedules
**Solution:** Review vessel call times and adjust ETB/ATB

### All Slots Show 0% Utilization
**Cause:** Date range doesn't match vessel call times
**Solution:** Verify fecha_desde and fecha_hasta filters

### Export Button Not Visible
**Cause:** Missing REPORT_EXPORT permission
**Solution:** Contact admin to grant permission

## 📱 Mobile Access

The report is responsive and works on:
- Desktop (optimal)
- Tablet (good)
- Mobile (basic functionality)

## 🔐 Security Notes

- All access is logged in `audit.audit_log`
- PII fields are masked in exports
- Rate limiting prevents abuse
- RBAC enforced on all endpoints

## 📞 Support

For issues or questions:
1. Check this guide
2. Review help section in the report
3. Contact system administrator
4. Check audit logs for access issues

## 🎓 Training Resources

**Recommended Reading:**
- SGCMI User Guide: `GUIA_USO_SISTEMA.md`
- R3 Implementation Summary: `R3_KPI_IMPLEMENTATION_SUMMARY.md`
- Frontend Quick Start: `TAILWIND_ALPINE_QUICKSTART.md`

**Video Tutorials:** (Coming soon)
- Basic report usage
- Advanced filtering
- Export and analysis
- Interpreting KPIs

---

**Last Updated**: November 30, 2025  
**Version**: 1.0  
**Status**: Production Ready ✅

