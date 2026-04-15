USE [orders]
GO

/****** Object:  Table [dbo].[mpesa_transactions]    Script Date: 3/22/2026 5:17:56 PM ******/
SET ANSI_NULLS ON
GO

SET QUOTED_IDENTIFIER ON
GO

CREATE TABLE [dbo].[mpesa_transactions](
	[id] [bigint] IDENTITY(1,1) NOT NULL,
	[FirstName] [nvarchar](255) NULL,
	[MiddleName] [nvarchar](255) NULL,
	[LastName] [nvarchar](255) NULL,
	[TransactionType] [nvarchar](255) NULL,
	[TransID] [nvarchar](255) NOT NULL,
	[TransTime] [nvarchar](255) NULL,
	[BusinessShortCode] [nvarchar](255) NULL,
	[BillRefNumber] [nvarchar](255) NULL,
	[InvoiceNumber] [nvarchar](255) NULL,
	[ThirdPartyTransID] [nvarchar](255) NULL,
	[MSISDN] [nvarchar](255) NULL,
	[TransAmount] [decimal](38, 2) NULL,
	[OrgAccountBalance] [decimal](38, 2) NULL,
	[PaymentType] [int] NOT NULL,
	[is_claimed] [tinyint] NOT NULL,
	[created_at] [datetime] NOT NULL,
	[updated_at] [datetime] NOT NULL,
PRIMARY KEY CLUSTERED 
(
	[id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY]
GO

ALTER TABLE [dbo].[mpesa_transactions] ADD  DEFAULT ('1') FOR [PaymentType]
GO

ALTER TABLE [dbo].[mpesa_transactions] ADD  DEFAULT ('0') FOR [is_claimed]
GO

ALTER TABLE [dbo].[mpesa_transactions] ADD  DEFAULT (getdate()) FOR [created_at]
GO

ALTER TABLE [dbo].[mpesa_transactions] ADD  DEFAULT (getdate()) FOR [updated_at]
GO


